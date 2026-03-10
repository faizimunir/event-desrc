<?php

namespace App\Services;

use App\Jobs\ProcessMediaVariants;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;

class MediaService
{
    public function __construct(
        protected ImageManager $imageManager
    ) {}

    public function upload(UploadedFile $file, Model $model, string $collection): Media
    {
        $config = config('media.collections');
        if (! isset($config[$collection])) {
            throw new \InvalidArgumentException("Collection [{$collection}] is not defined in config/media.php.");
        }

        Validator::validate(
            [
                'file' => $file,
                'size' => $file->getSize(),
            ],
            [
                'file' => 'required|mimes:jpg,jpeg,png,webp',
                'size' => 'max:'.(config('media.max_upload_size_kb', 2048) * 1024),
            ],
            [
                'file.mimes' => 'File must be jpg, jpeg, png or webp.',
                'size.max' => 'File size must not exceed '.config('media.max_upload_size_kb').' KB.',
            ]
        );

        $id = (string) \Illuminate\Support\Str::uuid();
        $disk = 'public';
        $basePath = 'media/'.$id;
        $originalPath = $basePath.'/original.webp';

        $media = Media::create([
            'id' => $id,
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'collection' => $collection,
            'disk' => $disk,
            'mime_type' => 'image/webp',
            'size' => 0,
            'meta' => null,
        ]);

        Storage::disk($disk)->makeDirectory($basePath);

        $image = $this->imageManager->read($file->getRealPath());
        $maxWidth = (int) config('media.max_original_width', 2000);
        $qualityOriginal = (int) config('media.quality_original', 85);
        $collectionConfig = $config[$collection];

        if (isset($collectionConfig['force_ratio']) && is_array($collectionConfig['force_ratio']) && count($collectionConfig['force_ratio']) >= 2) {
            [$ratioW, $ratioH] = $collectionConfig['force_ratio'];
            $image->cover($maxWidth, (int) round($maxWidth * $ratioH / $ratioW), 'center');
        } elseif (! empty($collectionConfig['keep_ratio'])) {
            $image->scaleDown($maxWidth, null);
        } else {
            $image->scaleDown($maxWidth, null);
        }

        $encoded = $image->toWebp(quality: $qualityOriginal);
        $fullPath = Storage::disk($disk)->path($originalPath);
        $encoded->save($fullPath);

        $media->update([
            'size' => (int) Storage::disk($disk)->size($originalPath),
            'meta' => [
                'original_width' => $image->width(),
                'original_height' => $image->height(),
                'variants' => [],
            ],
        ]);

        ProcessMediaVariants::dispatch($media);

        return $media->fresh();
    }
}

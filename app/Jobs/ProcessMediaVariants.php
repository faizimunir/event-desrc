<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ProcessMediaVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Media $media
    ) {}

    public function handle(ImageManager $imageManager): void
    {
        $config = config('media.collections');
        $collection = $this->media->collection;
        if (! isset($config[$collection])) {
            return;
        }

        $collectionConfig = $config[$collection];
        $variants = $collectionConfig['variants'] ?? [];
        if ($variants === []) {
            return;
        }

        $disk = $this->media->disk;
        $basePath = 'media/'.$this->media->id;
        $originalPath = $basePath.'/original.webp';

        if (! Storage::disk($disk)->exists($originalPath)) {
            return;
        }

        $fullPath = Storage::disk($disk)->path($originalPath);
        $image = $imageManager->read($fullPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $qualityVariant = (int) config('media.quality_variant', 80);
        $forceRatio = $collectionConfig['force_ratio'] ?? null;
        $keepRatio = ! empty($collectionConfig['keep_ratio']);
        $createdVariants = [];

        foreach ($variants as $width) {
            $variantPath = $basePath.'/'.$width.'.webp';
            if (Storage::disk($disk)->exists($variantPath)) {
                $createdVariants[] = $width;
                continue;
            }

            $variantImage = $imageManager->read($fullPath);

            if (is_array($forceRatio) && count($forceRatio) >= 2) {
                [$ratioW, $ratioH] = $forceRatio;
                $height = (int) round($width * $ratioH / $ratioW);
                $variantImage->cover($width, $height, 'center');
            } elseif ($keepRatio) {
                $variantImage->scaleDown($width, null);
            } else {
                $variantImage->scaleDown($width, null);
            }

            $encoded = $variantImage->toWebp(quality: $qualityVariant);
            $variantFullPath = Storage::disk($disk)->path($variantPath);
            $encoded->save($variantFullPath);
            $createdVariants[] = $width;
        }

        $meta = $this->media->meta ?? [];
        $meta['original_width'] = $originalWidth;
        $meta['original_height'] = $originalHeight;
        $meta['variants'] = $createdVariants;
        $this->media->update(['meta' => $meta]);
    }
}

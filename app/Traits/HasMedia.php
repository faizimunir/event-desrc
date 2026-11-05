<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    protected static function bootHasMedia(): void
    {
        static::deleting(function (self $model) {
            $model->media()->each(fn (Media $media) => $media->delete());
        });
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function getMedia(string $collection)
    {
        return $this->media()->where('collection', $collection)->get();
    }

    public function getFirstMediaUrl(string $collection, ?int $size = null): ?string
    {
        $media = $this->media()->where('collection', $collection)->first();

        return $media?->getUrl($size);
    }

    /**
     * Normalisasi URL media untuk tampilan:
     * - path /media/{id} (privat) atau /storage/... (publik) dibiarkan
     * - URL absolut legacy dipotong ke path-nya agar host APP_URL tidak merusak img src
     */
    public static function normalizePublicMediaUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/storage/') || str_starts_with($url, '/media/')) {
            return $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && (str_starts_with($path, '/storage/') || str_starts_with($path, '/media/'))) {
                return $path;
            }
        }

        return $url;
    }

    public function deleteMediaCollection(string $collection): void
    {
        $this->media()->where('collection', $collection)->each(function (Media $media) {
            $media->delete();
        });
    }
}

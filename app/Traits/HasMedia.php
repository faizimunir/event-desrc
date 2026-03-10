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

    public function deleteMediaCollection(string $collection): void
    {
        $this->media()->where('collection', $collection)->each(function (Media $media) {
            $media->delete();
        });
    }
}

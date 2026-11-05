<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'model_type',
        'model_id',
        'collection',
        'disk',
        'mime_type',
        'size',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Media $media) {
            if (empty($media->id)) {
                $media->id = (string) Str::ulid();
            }
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPrivate(): bool
    {
        return (bool) config("media.collections.{$this->collection}.private", false);
    }

    /**
     * Disk tempat file benar-benar ada (dukung legacy public untuk koleksi yang kini privat).
     */
    public function resolveDiskForPath(?string $path = null): ?string
    {
        $path ??= $this->getPath();
        $candidates = array_values(array_unique(array_filter([
            $this->disk,
            'local',
            'public',
        ])));

        foreach ($candidates as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public function getPath(?int $size = null): string
    {
        $base = 'media/'.$this->id;

        if ($size === null) {
            return $base.'/original.webp';
        }

        $variants = $this->meta['variants'] ?? [];
        if (! in_array($size, $variants, true)) {
            return $base.'/original.webp';
        }

        $path = $base.'/'.$size.'.webp';
        $disk = $this->resolveDiskForPath($path) ?? $this->disk;
        if (! Storage::disk($disk)->exists($path)) {
            return $base.'/original.webp';
        }

        return $path;
    }

    public function getUrl(?int $size = null): string
    {
        if ($this->isPrivate()) {
            return route('media.show', array_filter([
                'media' => $this->id,
                'size' => $size,
            ]), absolute: false);
        }

        $path = $this->getPath($size);
        $url = Storage::disk($this->disk)->url($path);

        // Disk lokal/public: path relatif agar APP_URL mismatch tidak merusak tampilan.
        if (in_array($this->disk, ['public', 'local'], true)) {
            $parsedPath = parse_url($url, PHP_URL_PATH);
            if (is_string($parsedPath) && $parsedPath !== '') {
                $query = parse_url($url, PHP_URL_QUERY);

                return $parsedPath.($query ? '?'.$query : '');
            }
        }

        return $url;
    }
}

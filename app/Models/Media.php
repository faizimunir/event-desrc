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
                $media->id = (string) Str::uuid();
            }
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
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
        if (! Storage::disk($this->disk)->exists($path)) {
            return $base.'/original.webp';
        }

        return $path;
    }

    public function getUrl(?int $size = null): string
    {
        return Storage::disk($this->disk)->url($this->getPath($size));
    }
}

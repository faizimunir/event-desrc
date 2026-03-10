<?php

namespace App\Observers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaObserver
{
    public function deleted(Media $media): void
    {
        $path = 'media/'.$media->id;
        $disk = $media->disk;
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->deleteDirectory($path);
        }
    }
}

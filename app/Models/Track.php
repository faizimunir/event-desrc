<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'material',
        'long_track',
        'photo_track',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function photoTrackUrl(): ?string
    {
        return $this->photo_track ? Storage::disk('public')->url($this->photo_track) : null;
    }
}

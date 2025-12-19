<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'channel',
        'subject',
        'content',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}


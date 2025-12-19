<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveResultCategory extends Model
{
    protected $fillable = [
        'event_id',
        'title',
        'spreadsheet_id',
        'rounds', // Keep for backward compatibility
        'selected_sheets',
        'last_sync',
        'order',
        'is_active',
    ];

    protected $casts = [
        'rounds' => 'array',
        'selected_sheets' => 'array',
        'is_active' => 'boolean',
        'last_sync' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

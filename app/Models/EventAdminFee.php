<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAdminFee extends Model
{
    protected $fillable = [
        'event_id',
        'fee_amount',
        'fee_type',
        'fee_percentage',
        'description',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}


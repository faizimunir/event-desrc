<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    protected $fillable = [
        'event_id',
        'bank_name',
        'account_number',
        'account_name',
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


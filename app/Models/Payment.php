<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'participant_id',
        'payment_method',
        'amount',
        'payment_date',
        'payment_proof',
        'transaction_id',
        'payment_reference',
        'notes',
        'status',
        'verified_by',
        'payment_verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }
}

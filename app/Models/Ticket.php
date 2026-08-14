<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'registration_id',
        'ticket_code',
        'manual_wa_send_count',
    ];

    protected function casts(): array
    {
        return [
            'manual_wa_send_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_code)) {
                $ticket->ticket_code = 'TKT-'.Str::ulid();
            }
        });
    }

    /** Route binding by ticket_code (untuk URL public). */
    public function getRouteKeyName(): string
    {
        return 'ticket_code';
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /** URL untuk verifikasi / scan QR (isi QR code). */
    public function getVerificationUrlAttribute(): string
    {
        return route('tickets.verify', ['ticket' => $this->ticket_code], true);
    }
}

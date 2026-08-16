<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCheckin extends Model
{
    protected $table = 'event_checkin';

    protected $fillable = [
        'event_id',
        'registration_id',
        'checked_in_at',
        'checked_in_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EventCheckin $checkin): void {
            // Always set from app timezone. Relying on MySQL CURRENT_TIMESTAMP
            // stores UTC when the DB session is UTC, which then displays ~7–8h early.
            if ($checkin->checked_in_at === null) {
                $checkin->checked_in_at = now();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}

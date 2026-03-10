<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCodeAccess extends Model
{
    protected $table = 'event_code_access';

    protected $fillable = [
        'event_id',
        'code',
        'name',
        'valid_from',
        'valid_until',
        'usage_limit',
        'times_used',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'usage_limit' => 'integer',
            'times_used' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isValid(?\DateTimeInterface $at = null): bool
    {
        $at = $at ?? now();

        if ($this->valid_from && $at < $this->valid_from) {
            return false;
        }
        if ($this->valid_until && $at > $this->valid_until) {
            return false;
        }
        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function incrementUse(): void
    {
        $this->increment('times_used');
    }
}

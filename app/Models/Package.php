<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $table = 'event_packages';

    protected $fillable = [
        'event_id',
        'name',
        'price',
        'quota',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quota' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Sisa kuota paket (early bird/terbatas). null = tanpa batas.
     */
    public function remainingQuota(): ?int
    {
        if ($this->quota === null) {
            return null;
        }
        $used = $this->registrations()->count();
        return max(0, (int) $this->quota - $used);
    }

    /** Apakah paket punya batas kuota dan sudah penuh. */
    public function isQuotaFull(): bool
    {
        $remaining = $this->remainingQuota();
        return $remaining !== null && $remaining <= 0;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'package_id');
    }

    public function rewards(): BelongsToMany
    {
        return $this->belongsToMany(Reward::class, 'event_package_reward', 'event_package_id', 'reward_id')
            ->withPivot('photo_reward')
            ->withTimestamps();
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }
}

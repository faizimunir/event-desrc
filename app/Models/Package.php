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

    public const STATUS_ACTIVE = 'active';

    public const STATUS_NOT_ACTIVE = 'not_active';

    protected $table = 'event_packages';

    protected $fillable = [
        'event_id',
        'name',
        'price',
        'quota',
        'hide_quota',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quota' => 'integer',
            'hide_quota' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Sisa kuota paket (early bird/terbatas). null = tanpa batas.
     * Hanya registrasi yang "memegang" slot: pending+approved DAN punya order yang masih aktif:
     * - status paid, ATAU
     * - status pending_payment DAN belum lewat expired_at (order expired/cancelled atau sudah lewat waktu tidak dihitung).
     */
    public function remainingQuota(): ?int
    {
        if ($this->quota === null) {
            return null;
        }
        $used = $this->registrations()
            ->countsTowardQuota()
            ->whereHas('order', function ($q) {
                $q->where('status', Order::STATUS_PAID)
                    ->orWhere(function ($q2) {
                        $q2->where('status', Order::STATUS_PENDING_PAYMENT)
                            ->where(function ($q3) {
                                $q3->whereNull('expired_at')
                                    ->orWhere('expired_at', '>=', now());
                            });
                    });
            })
            ->count();
        return max(0, (int) $this->quota - $used);
    }

    /** Apakah paket punya batas kuota dan sudah penuh. */
    public function isQuotaFull(): bool
    {
        $remaining = $this->remainingQuota();
        return $remaining !== null && $remaining <= 0;
    }

    /** Apakah kuota sudah terisi penuh oleh order paid (confirmed) — tampil "Sold out". */
    public function isSoldOut(): bool
    {
        if ($this->quota === null) {
            return false;
        }
        $paidCount = $this->registrations()
            ->countsTowardQuota()
            ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PAID))
            ->count();

        return $paidCount >= $this->quota;
    }

    /** Apakah paket aktif (bisa dipilih saat registrasi). */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
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

    /** Apakah paket ini punya reward jersey (deteksi dari nama reward). */
    public function hasJerseyReward(): bool
    {
        return $this->rewards->contains(fn (Reward $r) => str_contains(strtolower($r->name ?? ''), 'jersey'));
    }
}

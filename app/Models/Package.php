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
        'admin_fee',
        'admin_fee_included_in_price',
        'quota',
        'hide_quota',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'admin_fee_included_in_price' => 'boolean',
            'quota' => 'integer',
            'hide_quota' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Sisa kuota paket (early bird/terbatas). null = tanpa batas.
     * Hanya order pending + confirmed yang mengikat kuota (draft tidak).
     */
    public function remainingQuota(): ?int
    {
        if ($this->quota === null) {
            return null;
        }
        $used = $this->registrations()
            ->countsTowardQuota()
            ->whereHas('order', fn ($q) => $q->holdsQuota())
            ->count();

        return max(0, (int) $this->quota - $used);
    }

    /** Apakah paket punya batas kuota dan sudah penuh. */
    public function isQuotaFull(): bool
    {
        $remaining = $this->remainingQuota();

        return $remaining !== null && $remaining <= 0;
    }

    /** Tidak ada slot tersisa (hold + confirmed memenuhi kuota). */
    public function isSoldOut(): bool
    {
        if ($this->quota === null) {
            return false;
        }
        $held = $this->registrations()
            ->countsTowardQuota()
            ->whereHas('order', fn ($q) => $q->holdsQuota())
            ->count();

        return $held >= $this->quota;
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
        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }

    /** Total yang dibayar peserta (harga paket + biaya admin jika di luar harga). */
    public function payableAmount(): float
    {
        $base = round((float) $this->price, 2);
        if ($this->admin_fee_included_in_price) {
            return $base;
        }

        return round($base + (float) $this->admin_fee, 2);
    }

    public function getFormattedPayableAmountAttribute(): string
    {
        return 'Rp '.number_format($this->payableAmount(), 0, ',', '.');
    }

    public function getFormattedAdminFeeAttribute(): string
    {
        return 'Rp '.number_format((float) $this->admin_fee, 0, ',', '.');
    }

    public function hasAdminFee(): bool
    {
        return (float) $this->admin_fee > 0;
    }

    public function adminFeeIsIncludedInPrice(): bool
    {
        return (bool) $this->admin_fee_included_in_price;
    }

    /** Apakah paket ini punya reward jersey (deteksi dari nama reward). */
    public function hasJerseyReward(): bool
    {
        return $this->rewards->contains(fn (Reward $r) => str_contains(strtolower($r->name ?? ''), 'jersey'));
    }
}

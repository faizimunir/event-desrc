<?php

namespace App\Models;

use App\Services\QuotaReservationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    /** Baru isi form; belum Confirm & Pay — tidak mengikat kuota. */
    public const STATUS_DRAFT = 'draft';

    /** Sudah confirm; kuota di-hold; menunggu bayar. */
    public const STATUS_PENDING = 'pending';

    /** Pembayaran sukses (PAID pertama valid). */
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Setelah event selesai (opsional / reporting). */
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    public const PAYMENT_STATUS_PAID = 'paid';

    /** Order dibatalkan karena lewat expired_at (scheduler). */
    public const PAYMENT_STATUS_EXPIRED = 'expired';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_UNPAID,
        self::PAYMENT_STATUS_PAID,
        self::PAYMENT_STATUS_EXPIRED,
    ];

    /** Batas waktu draft (belum commit beli). */
    public const ORDER_CONFIRMATION_DEADLINE_MINUTES = 5;

    /** @deprecated Use ORDER_CONFIRMATION_DEADLINE_MINUTES */
    public const PAYMENT_DEADLINE_MINUTES = self::ORDER_CONFIRMATION_DEADLINE_MINUTES;

    protected $fillable = [
        'order_code',
        'registration_id',
        'session_id',
        'user_id',
        'status',
        'payment_status',
        'expired_at',
        'confirmed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_code)) {
                $order->order_code = 'ORD-'.Str::ulid();
            }
            if (empty($order->status)) {
                $order->status = self::STATUS_DRAFT;
            }
            if ($order->status === self::STATUS_DRAFT && $order->expired_at === null) {
                $order->expired_at = now()->addMinutes(self::ORDER_CONFIRMATION_DEADLINE_MINUTES);
            }
            if ($order->status === self::STATUS_PENDING
                && $order->payment_status === self::PAYMENT_STATUS_UNPAID
                && $order->expired_at === null) {
                $order->expired_at = now()->addMinutes(Payment::PAYMENT_PROOF_DEADLINE_MINUTES);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'order_code';
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Percobaan pembayaran pending terakhir (satu aktif per order). */
    public function activePendingPayment(): ?Payment
    {
        return $this->payments()->where('status', Payment::STATUS_PENDING)->latest('id')->first();
    }

    /** Pembayaran sukses pertama (yang mengikat). */
    public function winningPayment(): ?Payment
    {
        return $this->payments()->where('status', Payment::STATUS_SUCCESS)->orderBy('id')->first();
    }

    /**
     * Arsipkan semua pending lalu buat baris payment baru (retry / ganti metode).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createNewPaymentAttempt(array $attributes): Payment
    {
        return DB::transaction(function () use ($attributes) {
            $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->first();

            $this->payments()
                ->where('status', Payment::STATUS_PENDING)
                ->update(['status' => Payment::STATUS_EXPIRED]);

            return $this->payments()->create(array_merge([
                'registration_id' => $this->registration_id,
                'expires_at' => $this->expired_at,
            ], $attributes));
        });
    }

    /** Order mengikat kuota: pending (hold) atau confirmed (fix). */
    public function scopeHoldsQuota($query): void
    {
        $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function scopeForSession($query, ?string $sessionId): void
    {
        if ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->whereNull('id');
        }
    }

    public function scopeForUser($query, ?int $userId): void
    {
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('id');
        }
    }

    public function scopeForCurrentVisitor($query, ?string $sessionId, ?int $userId): void
    {
        $query->where(function ($q) use ($sessionId, $userId) {
            if ($sessionId) {
                $q->orWhere('session_id', $sessionId);
            }
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
            if (! $sessionId && ! $userId) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function scopePendingPayment($query): void
    {
        $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING])
            ->whereHas('registration', function ($q) {
                $q->where('registrations.status', Registration::STATUS_PENDING);
            })
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('status', self::STATUS_DRAFT)
                        ->where(function ($q2) {
                            $q2->whereNull('expired_at')
                                ->orWhere('expired_at', '>=', now());
                        });
                })->orWhere(function ($q1) {
                    $q1->where('status', self::STATUS_PENDING)
                        ->where('payment_status', self::PAYMENT_STATUS_UNPAID)
                        ->where(function ($q2) {
                            $q2->where(function ($q3) {
                                $q3->whereDoesntHave('payments')
                                    ->where(function ($q4) {
                                        $q4->whereNull('expired_at')
                                            ->orWhere('expired_at', '>=', now());
                                    });
                            })->orWhereHas('payments', function ($pq) {
                                $pq->where('status', Payment::STATUS_PENDING)
                                    ->where(function ($q3) {
                                        $q3->whereNotNull('transfer_proof_path')
                                            ->orWhereNull('expires_at')
                                            ->orWhere('expires_at', '>=', now());
                                    });
                            });
                        });
                });
            });
    }

    /** Draft lewat batas (scheduler). */
    public function scopeExpiredDraft($query): void
    {
        $query->where('status', self::STATUS_DRAFT)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now());
    }

    /** Pending unpaid lewat batas bayar (scheduler). */
    public function scopeExpiredPendingUnpaid($query): void
    {
        $query->where('status', self::STATUS_PENDING)
            ->where('payment_status', self::PAYMENT_STATUS_UNPAID)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now());
    }

    /** @deprecated Gunakan scopeExpiredDraft */
    public function scopeExpiredPending($query): void
    {
        $query->expiredDraft();
    }

    public function scopeNotExpired($query): void
    {
        $query->where(function ($q) {
            $q->where('status', '!=', self::STATUS_DRAFT)
                ->orWhereNull('expired_at')
                ->orWhere('expired_at', '>=', now());
        })
            ->where(function ($q) {
                $q->where('status', '!=', self::STATUS_PENDING)
                    ->orWhere('payment_status', '!=', self::PAYMENT_STATUS_UNPAID)
                    ->orWhereNull('expired_at')
                    ->orWhere('expired_at', '>=', now())
                    ->orWhereDoesntHave('payments')
                    ->orWhereHas('payments', function ($pq) {
                        $pq->where('status', '!=', Payment::STATUS_PENDING)
                            ->orWhereNotNull('transfer_proof_path')
                            ->orWhereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
            });
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingUnpaid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->payment_status === self::PAYMENT_STATUS_UNPAID;
    }

    public function isPendingPayment(): bool
    {
        return $this->isDraft() || $this->isPendingUnpaid();
    }

    public function isConfirmedOrder(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /** Sudah lewat batas waktu (UI); pembatalan resmi oleh scheduler. */
    public function isExpired(): bool
    {
        if ($this->isCancelled()) {
            return true;
        }
        if ($this->isDraft()) {
            return $this->expired_at && $this->expired_at->isPast();
        }
        if ($this->isPendingUnpaid()) {
            $p = $this->activePendingPayment();
            if ($p && $p->isPending() && ! empty($p->transfer_proof_path)) {
                return false;
            }

            return $this->expired_at && $this->expired_at->isPast();
        }

        return false;
    }

    /** User sudah klik alur Confirm & Pay (step 2). */
    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * Draft → pending (hold kuota). Bracket + package + order di-lock (FOR UPDATE) agar tidak bentrok klik bersamaan.
     *
     * @return bool false jika kuota bracket/paket sudah penuh
     */
    public function finalizeForPayment(): bool
    {
        $this->loadMissing('registration');
        $reg = $this->registration;
        if (! $reg) {
            return false;
        }
        if (! $this->isDraft()) {
            return true;
        }

        $orderId = $this->getKey();
        $ok = QuotaReservationService::withLocks(
            $reg->bracket_id,
            $reg->package_id,
            $orderId,
            function () use ($orderId) {
                $order = self::query()->whereKey($orderId)->firstOrFail();
                if (! $order->isDraft()) {
                    return true;
                }
                $order->loadMissing('registration');
                $bracket = Bracket::query()->findOrFail($order->registration->bracket_id);
                $package = Package::query()->findOrFail($order->registration->package_id);
                if (! $bracket->hasQuota() || $package->isQuotaFull()) {
                    return false;
                }
                $minutes = Payment::PAYMENT_PROOF_DEADLINE_MINUTES;
                $order->forceFill([
                    'status' => self::STATUS_PENDING,
                    'payment_status' => self::PAYMENT_STATUS_UNPAID,
                    'expired_at' => now()->addMinutes($minutes),
                    'confirmed_at' => now(),
                ])->save();

                return true;
            }
        );

        $this->refresh();

        return (bool) $ok;
    }

    public static function createNewOrderForRegistration(Registration $registration): Order
    {
        $oldOrder = $registration->order;
        $sessionId = $oldOrder?->session_id;
        $userId = $oldOrder?->user_id;

        $oldOrder?->delete();

        return static::create([
            'registration_id' => $registration->id,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'status' => self::STATUS_DRAFT,
            'payment_status' => null,
            'expired_at' => now()->addMinutes(self::ORDER_CONFIRMATION_DEADLINE_MINUTES),
        ]);
    }

    public function isOwnedByCurrentVisitor(): bool
    {
        $sessionId = session()->getId();
        $userId = auth()->id();

        if ($this->session_id && $this->session_id === $sessionId) {
            return true;
        }
        if ($userId && $this->user_id === $userId) {
            return true;
        }

        return false;
    }

    /** Lunas & sah (siap verifikasi admin tiket). */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID
            && in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_COMPLETED], true);
    }

    public function getProofUploadedAttribute(): bool
    {
        $p = $this->activePendingPayment();

        return $p && $p->isPending() && ! empty($p->transfer_proof_path);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_CONFIRMED) {
            return __('Confirmed');
        }
        if ($this->status === self::STATUS_COMPLETED) {
            return __('Completed');
        }
        if ($this->status === self::STATUS_CANCELLED) {
            return __('Cancelled');
        }
        if ($this->isDraft()) {
            return __('Draft');
        }
        if ($this->isPaid()) {
            return __('Confirmed');
        }
        if ($this->isExpired()) {
            return __('Expired');
        }
        $p = $this->activePendingPayment();
        if ($p) {
            if ($p->isPending() && ! empty($p->transfer_proof_path)) {
                return __('Payment Submitted');
            }
            if ($p->isPending()) {
                return __('Waiting payment');
            }
            if ($p->isRejected()) {
                return __('Payment rejected');
            }
        }

        return __('Waiting payment');
    }
}

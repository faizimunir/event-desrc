<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    /** Menit untuk konfirmasi order (klik Pay now) sebelum order dianggap expired. */
    public const ORDER_CONFIRMATION_DEADLINE_MINUTES = 15;

    /** @deprecated Use ORDER_CONFIRMATION_DEADLINE_MINUTES */
    public const PAYMENT_DEADLINE_MINUTES = self::ORDER_CONFIRMATION_DEADLINE_MINUTES;

    protected $fillable = [
        'order_code',
        'registration_id',
        'session_id',
        'user_id',
        'status',
        'expired_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_code)) {
                $order->order_code = 'ORD-'.Str::ulid();
            }
            if (empty($order->status)) {
                $order->status = self::STATUS_PENDING_PAYMENT;
            }
            if ($order->status === self::STATUS_PENDING_PAYMENT && $order->expired_at === null) {
                $order->expired_at = now()->addMinutes(self::ORDER_CONFIRMATION_DEADLINE_MINUTES);
            }
        });
    }

    /** Route binding menggunakan order_code (ORD-ULID). */
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

    /** Scope: order milik session saat ini (guest). */
    public function scopeForSession($query, ?string $sessionId): void
    {
        if ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->whereNull('id'); // no match
        }
    }

    /** Scope: order milik user (logged-in). */
    public function scopeForUser($query, ?int $userId): void
    {
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('id');
        }
    }

    /** Scope: order yang masih menunggu pembayaran (status pending_payment, belum lunas, belum kadaluarsa). */
    public function scopePendingPayment($query): void
    {
        $query->where('status', self::STATUS_PENDING_PAYMENT)
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            })
            ->whereHas('registration', function ($q) {
                $q->where('registrations.status', Registration::STATUS_PENDING);
            });
    }

    /** Scope: order yang sudah lewat expired_at dan masih pending_payment (untuk job cancel). */
    public function scopeExpiredPending($query): void
    {
        $query->where('status', self::STATUS_PENDING_PAYMENT)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now());
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public function isPaidStatus(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpiredStatus(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /** Order sudah lewat batas konfirmasi (15 menit) dan belum dikonfirmasi (klik Pay now). */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }
        if ($this->confirmed_at) {
            return false;
        }
        return $this->isPendingPayment() && $this->expired_at && $this->expired_at->isPast();
    }

    /** Order sudah dikonfirmasi (user pernah buka halaman payment). */
    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * Buat order baru (order_code baru) untuk registration ini.
     * Dipakai saat payment expired: order lama tidak dipakai lagi, buat order baru dengan order_code baru.
     */
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
            'status' => self::STATUS_PENDING_PAYMENT,
            'expired_at' => now()->addMinutes(self::ORDER_CONFIRMATION_DEADLINE_MINUTES),
        ]);
    }

    /** Apakah order ini milik visitor saat ini (session atau user)? */
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

    /** Sudah punya pembayaran yang success? (order status paid atau payment success). */
    public function isPaid(): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return true;
        }
        $payment = $this->registration->payment ?? null;

        return $payment && $payment->isSuccess();
    }

    /** Bukti transfer sudah di-upload (payment pending dan ada file bukti). */
    public function getProofUploadedAttribute(): bool
    {
        $payment = $this->registration->payment ?? null;

        return $payment && $payment->isPending() && ! empty($payment->transfer_proof_path);
    }

    /** Status label untuk tampilan. */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return __('Paid');
        }
        if ($this->status === self::STATUS_CANCELLED) {
            return __('Cancelled');
        }
        if ($this->status === self::STATUS_EXPIRED) {
            return __('Expired');
        }
        if ($this->isPaid()) {
            return __('Paid');
        }
        if ($this->isExpired()) {
            return __('Expired');
        }
        $payment = $this->registration->payment;
        if ($payment) {
            if ($payment->isPending() && ! empty($payment->transfer_proof_path)) {
                return __('Payment Submitted');
            }
            if ($payment->isPending()) {
                return __('Waiting payment');
            }
            if ($payment->isRejected()) {
                return __('Payment rejected');
            }
        }

        return __('Waiting payment');
    }
}

<?php

namespace App\Models;

use App\Services\ManualTransferNotifier;
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

    /** Sudah Confirm & Pay; kuota di-hold; menunggu bayar / bukti. */
    public const STATUS_UNPAID = 'unpaid';

    /** @deprecated Use STATUS_UNPAID */
    public const STATUS_PENDING = self::STATUS_UNPAID;

    /** Pembayaran terverifikasi (nominal / bukti sah). */
    public const STATUS_PAID = 'paid';

    /** @deprecated Use STATUS_PAID */
    public const STATUS_CONFIRMED = self::STATUS_PAID;

    public const STATUS_CANCELLED = 'cancelled';

    /** Setelah event selesai (opsional / reporting). */
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_UNPAID,
        self::STATUS_PAID,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
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
            if ($order->status === self::STATUS_UNPAID && $order->expired_at === null) {
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

    /**
     * Percobaan bayar aktif: menunggu transfer atau menunggu verifikasi admin (submitted).
     */
    public function activePendingPayment(): ?Payment
    {
        return $this->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
            ->latest('id')
            ->first();
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
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
                ->update(['status' => Payment::STATUS_EXPIRED]);

            return $this->payments()->create(array_merge([
                'registration_id' => $this->registration_id,
                'expires_at' => $this->expired_at,
            ], $attributes));
        });
    }

    /** Order mengikat kuota: unpaid (hold) atau paid (fix). */
    public function scopeHoldsQuota($query): void
    {
        $query->whereIn('status', [self::STATUS_UNPAID, self::STATUS_PAID]);
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

    /**
     * Sembunyikan order yang batal karena draft lewat 5 menit tanpa Confirm & Pay
     * (order + registration cancelled, confirmed_at null) dari daftar peserta.
     */
    public function scopeExcludeAbandonedDraftTimeout($query): void
    {
        $query->whereNot(function ($q) {
            $q->where('orders.status', self::STATUS_CANCELLED)
                ->whereNull('orders.confirmed_at')
                ->whereHas('registration', function ($r) {
                    $r->where('registrations.status', Registration::STATUS_CANCELLED);
                });
        });
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
        $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_UNPAID])
            ->whereHas('registration', function ($q) {
                $q->whereIn('registrations.status', [
                    Registration::STATUS_PENDING,
                    Registration::STATUS_APPROVED,
                ]);
            })
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('status', self::STATUS_DRAFT)
                        ->where(function ($q2) {
                            $q2->whereNull('expired_at')
                                ->orWhere('expired_at', '>=', now());
                        });
                })->orWhere(function ($q1) {
                    $q1->where('status', self::STATUS_UNPAID)
                        ->where(function ($q2) {
                            $q2->where(function ($q3) {
                                $q3->whereDoesntHave('payments')
                                    ->where(function ($q4) {
                                        $q4->whereNull('expired_at')
                                            ->orWhere('expired_at', '>=', now());
                                    });
                            })->orWhereHas('payments', function ($pq) {
                                $pq->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
                                    ->where(function ($q3) {
                                        $q3->where('status', Payment::STATUS_SUBMITTED)
                                            ->orWhereNotNull('transfer_proof_path')
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

    /**
     * Terapkan pembatalan draft lewat deadline ke DB (hapus payments, order cancelled, registration cancelled).
     * Idempotent. Dipanggil saat akses halaman / daftar agar status konsisten walau scheduler belum jalan.
     */
    public function enforceExpiredDraftIfNeeded(): bool
    {
        if (! $this->isDraft() || ! $this->expired_at || ! $this->expired_at->isPast()) {
            return false;
        }

        $changed = (bool) DB::transaction(function () {
            $order = static::query()->whereKey($this->getKey())->lockForUpdate()->first();
            if (! $order || ! $order->isDraft() || ! $order->expired_at || ! $order->expired_at->isPast()) {
                return false;
            }
            $order->payments()->delete();
            $order->update(['status' => self::STATUS_CANCELLED]);
            $reg = Registration::query()->whereKey($order->registration_id)->lockForUpdate()->first();
            if ($reg && $reg->isPending()) {
                $reg->update(['status' => Registration::STATUS_CANCELLED]);
            }

            return true;
        });

        if ($changed) {
            $this->refresh();
        }

        return $changed;
    }

    /** Batalkan semua draft kedaluwarsa untuk event (admin list / detail). */
    public static function enforceExpiredDraftsForEvent(int $eventId): void
    {
        static::query()
            ->expiredDraft()
            ->whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
            ->orderBy('id')
            ->each(fn (Order $order) => $order->enforceExpiredDraftIfNeeded());
    }

    /** Batalkan draft kedaluwarsa milik visitor (halaman pesanan saya). */
    public static function enforceExpiredDraftsForVisitor(?string $sessionId, ?int $userId): void
    {
        static::query()
            ->expiredDraft()
            ->forCurrentVisitor($sessionId, $userId)
            ->orderBy('id')
            ->each(fn (Order $order) => $order->enforceExpiredDraftIfNeeded());
    }

    /**
     * Sama persyaratan dengan scopeExpiredPendingUnpaid (unpaid, lewat deadline, tanpa payment submitted).
     */
    public function qualifiesForPaymentDeadlineExpiry(): bool
    {
        if (! $this->isPendingUnpaid() || ! $this->expired_at || ! $this->expired_at->isPast()) {
            return false;
        }
        if ($this->payments()->where('status', Payment::STATUS_SUBMITTED)->exists()) {
            return false;
        }
        if (! $this->payments()->exists()) {
            return true;
        }

        return $this->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->whereNull('transfer_proof_path')
            ->exists();
    }

    /**
     * Terapkan point 10: payment pending → expired, order cancelled, registration cancelled (pending/approved).
     * Lepas kuota. Idempotent.
     */
    public function enforceExpiredPaymentWindowIfNeeded(): bool
    {
        if (! $this->qualifiesForPaymentDeadlineExpiry()) {
            return false;
        }

        $notifyRegistrationId = null;
        $changed = (bool) DB::transaction(function () use (&$notifyRegistrationId) {
            $order = static::query()->whereKey($this->getKey())->lockForUpdate()->first();
            if (! $order || ! $order->qualifiesForPaymentDeadlineExpiry()) {
                return false;
            }
            $order->payments()->where('status', Payment::STATUS_PENDING)->update([
                'status' => Payment::STATUS_EXPIRED,
            ]);
            $order->update(['status' => self::STATUS_CANCELLED]);
            $reg = Registration::query()->whereKey($order->registration_id)->lockForUpdate()->first();
            if ($reg && ($reg->isPending() || $reg->isApproved())) {
                $reg->update(['status' => Registration::STATUS_CANCELLED]);
                $notifyRegistrationId = $reg->id;
            }

            return true;
        });

        if ($notifyRegistrationId) {
            $toNotify = Registration::query()->find($notifyRegistrationId);
            if ($toNotify) {
                ManualTransferNotifier::paymentExpired($toNotify);
            }
        }

        if ($changed) {
            $this->refresh();
        }

        return $changed;
    }

    public static function enforceExpiredPaymentWindowsForEvent(int $eventId): void
    {
        static::query()
            ->expiredPendingUnpaid()
            ->whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
            ->orderBy('id')
            ->each(fn (Order $order) => $order->enforceExpiredPaymentWindowIfNeeded());
    }

    public static function enforceExpiredPaymentWindowsForVisitor(?string $sessionId, ?int $userId): void
    {
        static::query()
            ->expiredPendingUnpaid()
            ->forCurrentVisitor($sessionId, $userId)
            ->orderBy('id')
            ->each(fn (Order $order) => $order->enforceExpiredPaymentWindowIfNeeded());
    }

    /**
     * Unpaid lewat batas bayar/upload bukti: hanya jika tidak ada payment submitted
     * (menunggu admin tidak di-expire otomatis oleh deadline order).
     */
    public function scopeExpiredPendingUnpaid($query): void
    {
        $query->where('status', self::STATUS_UNPAID)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->whereDoesntHave('payments', function ($pq) {
                $pq->where('status', Payment::STATUS_SUBMITTED);
            })
            ->where(function ($q) {
                $q->whereDoesntHave('payments')
                    ->orWhereHas('payments', function ($pq) {
                        $pq->where('status', Payment::STATUS_PENDING)
                            ->whereNull('transfer_proof_path');
                    });
            });
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
                $q->where('status', '!=', self::STATUS_UNPAID)
                    ->orWhereNull('expired_at')
                    ->orWhere('expired_at', '>=', now())
                    ->orWhereDoesntHave('payments')
                    ->orWhereHas('payments', function ($pq) {
                        $pq->whereNotIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUBMITTED])
                            ->orWhere('status', Payment::STATUS_SUBMITTED)
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
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPendingPayment(): bool
    {
        return $this->isDraft() || $this->isPendingUnpaid();
    }

    public function isConfirmedOrder(): bool
    {
        return $this->status === self::STATUS_PAID;
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
            if ($p && $p->isSubmitted()) {
                return false;
            }
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
                    'status' => self::STATUS_UNPAID,
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
        return $this->winningPayment() !== null
            && in_array($this->status, [self::STATUS_PAID, self::STATUS_COMPLETED], true);
    }

    public function getProofUploadedAttribute(): bool
    {
        $p = $this->activePendingPayment();

        return $p && ($p->isSubmitted() || ($p->isPending() && ! empty($p->transfer_proof_path)));
    }

    /** Admin: apakah order ini boleh di-reset batas waktu bayar / upload bukti. */
    public function adminCanResetPaymentDeadline(Registration $registration): bool
    {
        if ($registration->id !== $this->registration_id) {
            return false;
        }
        if ($registration->isCancelled() || ! $registration->isPending()) {
            return false;
        }
        if ($this->isPaid() || $this->proof_uploaded) {
            return false;
        }
        if ($this->status === self::STATUS_CANCELLED && $this->confirmed_at !== null && ! $this->isPaid()) {
            return true;
        }

        return $this->isPendingUnpaid()
            && $this->expired_at
            && $this->expired_at->isPast();
    }

    /**
     * Admin: perpanjang jendela pembayaran dari sekarang (order expired scheduler, atau pending lewat deadline tanpa bukti).
     * Mengembalikan null jika sukses, atau string pesan error (sudah diterjemahkan).
     */
    public function resetPaymentDeadlineForAdmin(): ?string
    {
        $this->loadMissing('registration.package', 'registration.bracket');
        $registration = $this->registration;

        if (! $registration || ! $this->adminCanResetPaymentDeadline($registration)) {
            return __('This order cannot reset the payment window in its current state.');
        }

        $restoringExpiredOrder = $this->status === self::STATUS_CANCELLED
            && $this->confirmed_at !== null
            && ! $this->isPaid();
        $extendDeadlineOnly = $this->isPendingUnpaid()
            && $this->expired_at
            && $this->expired_at->isPast();

        if (! $restoringExpiredOrder && ! $extendDeadlineOnly) {
            return __('This order cannot reset the payment window in its current state.');
        }

        $newExpiry = now()->addMinutes(Payment::PAYMENT_PROOF_DEADLINE_MINUTES);
        $amount = $registration->package ? $registration->package->payableAmount() : 0;

        if ($restoringExpiredOrder) {
            $ok = QuotaReservationService::withLocks(
                $registration->bracket_id,
                $registration->package_id,
                $this->getKey(),
                function () use ($registration, $newExpiry, $amount) {
                    $bracket = Bracket::query()->findOrFail($registration->bracket_id);
                    $package = Package::query()->findOrFail($registration->package_id);
                    if (! $bracket->hasQuota() || $package->isQuotaFull()) {
                        return false;
                    }

                    $this->forceFill([
                        'status' => self::STATUS_UNPAID,
                        'expired_at' => $newExpiry,
                    ])->save();

                    $this->createNewPaymentAttempt([
                        'amount' => $amount,
                        'method' => 'manual',
                        'status' => Payment::STATUS_PENDING,
                        'expires_at' => $newExpiry,
                        'manual_transfer_amount' => Payment::allocateUniqueManualTransferAmount((float) $amount),
                    ]);

                    return true;
                }
            );

            return $ok ? null : __('There is no remaining quota for this bracket or package.');
        }

        $extended = DB::transaction(function () use ($newExpiry, $amount) {
            $order = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if (! $order->isPendingUnpaid() || ! $order->expired_at || ! $order->expired_at->isPast()) {
                return false;
            }
            $order->forceFill(['expired_at' => $newExpiry])->save();

            $payment = $order->activePendingPayment();
            if ($payment && $payment->isPending()) {
                $payment->forceFill(['expires_at' => $newExpiry])->save();
            } else {
                $order->createNewPaymentAttempt([
                    'amount' => $amount,
                    'method' => 'manual',
                    'status' => Payment::STATUS_PENDING,
                    'expires_at' => $newExpiry,
                    'manual_transfer_amount' => Payment::allocateUniqueManualTransferAmount((float) $amount),
                ]);
            }

            return true;
        });

        return $extended ? null : __('This order cannot reset the payment window in its current state.');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return __('Paid');
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
            return __('Paid');
        }
        if ($this->isExpired()) {
            return __('Expired');
        }
        $p = $this->activePendingPayment();
        if ($p) {
            if ($p->isSubmitted() || ($p->isPending() && ! empty($p->transfer_proof_path))) {
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

    /**
     * Label alur checkout (peserta) selaras spesifikasi manual transfer.
     */
    public function participantCheckoutLabel(): string
    {
        $this->loadMissing(['registration', 'payments']);
        $reg = $this->registration;
        if (! $reg) {
            return $this->status_label;
        }

        $latest = $this->payments()->latest('id')->first();

        if ($reg->isRejected() && $this->isCancelled()) {
            if ($latest?->isFailed() && filled($latest->admin_notes)) {
                return __('Registration Rejected (:reason)', [
                    'reason' => Str::limit((string) $latest->admin_notes, 120),
                ]);
            }
            if ($latest?->isFailed()) {
                return __('Registration Rejected');
            }
            if ($latest?->isRefunded()) {
                return __('Registration Rejected');
            }

            return __('Registration Rejected');
        }

        if ($reg->isCancelled() && $this->isCancelled() && $latest?->isExpired()) {
            return __('Payment Expired');
        }

        if ($this->isCancelled()) {
            return __('Cancelled');
        }

        if ($this->isDraft() && $this->expired_at && $this->expired_at->isPast()) {
            return __('Expired');
        }

        if ($this->isDraft()) {
            return __('Confirm your order');
        }

        if ($this->isPaid() && $reg->isApproved() && $latest?->isSuccess()) {
            return __('Registration Confirmed');
        }

        if ($this->isPaid() && $reg->isPending() && $latest?->isSuccess()) {
            return __('Payment Received, Waiting Registration Approval');
        }

        if ($this->isPendingUnpaid() && $latest?->isFailed()) {
            return filled($latest->admin_notes)
                ? __('Payment Failed — :reason', ['reason' => Str::limit((string) $latest->admin_notes, 120)])
                : __('Payment Failed — "Invalid Transfer Proof"');
        }

        if ($this->isPendingUnpaid() && $latest && ($latest->isSubmitted() || ($latest->isPending() && ! empty($latest->transfer_proof_path)))) {
            return __('Waiting for Admin Confirmation');
        }

        if ($this->isPendingUnpaid() && $latest && $latest->isPending() && empty($latest->transfer_proof_path)) {
            return __('Waiting for payment');
        }

        if ($this->isPendingUnpaid() && $reg->isApproved()) {
            return __('Waiting for payment');
        }

        return $this->status_label;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'registration_id',
        'session_id',
        'user_id',
    ];

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

    /** Scope: order yang masih menunggu pembayaran (registration pending, belum lunas). */
    public function scopePendingPayment($query): void
    {
        $query->whereHas('registration', function ($q) {
            $q->where('registrations.status', Registration::STATUS_PENDING);
        });
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

    /** Sudah punya pembayaran yang approved? */
    public function isPaid(): bool
    {
        $payment = $this->registration->payment;

        return $payment && $payment->isApproved();
    }

    /** Status label untuk tampilan. */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isPaid()) {
            return __('Paid');
        }
        $payment = $this->registration->payment;
        if ($payment) {
            if ($payment->isPending()) {
                return __('Waiting verification');
            }
            if ($payment->isRejected()) {
                return __('Payment rejected');
            }
        }

        return __('Waiting payment');
    }
}

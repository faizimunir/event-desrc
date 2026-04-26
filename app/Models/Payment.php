<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    /** Menunggu transfer / upload bukti (belum ada bukti). */
    public const STATUS_PENDING = 'pending';

    /** Bukti sudah diunggah; menunggu verifikasi admin. */
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /** Registrasi ditolak admin tanpa pembayaran sukses — percobaan tidak berlaku. */
    public const STATUS_VOID = 'void';

    /** Pembayaran sukses lalu registrasi ditolak — arsip refund (proses di luar app). */
    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUBMITTED,
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
        self::STATUS_VOID,
        self::STATUS_REFUNDED,
    ];

    /** Menit untuk upload bukti transfer setelah order dikonfirmasi. */
    public const PAYMENT_PROOF_DEADLINE_MINUTES = 30;

    protected $fillable = [
        'order_id',
        'registration_id',
        'amount',
        'method',
        'manual_account_id',
        'manual_transfer_amount',
        'transfer_proof_path',
        'status',
        'expires_at',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
        'paid_at',
        'moota_transfer_amount',
        'moota_mutation_id',
        'moota_raw',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'manual_transfer_amount' => 'decimal:2',
            'moota_transfer_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
            'moota_raw' => 'array',
        ];
    }

    /** Batas upload bukti sudah lewat (payment pending dan expires_at sudah lewat). */
    public function isProofExpired(): bool
    {
        return $this->isPending() && $this->expires_at && $this->expires_at->isPast();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function manualAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'manual_account_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /** Admin menolak bukti / pembayaran tidak valid. */
    public function isRejected(): bool
    {
        return $this->isFailed();
    }

    public function isMoota(): bool
    {
        return $this->method === 'moota';
    }

    /** Batas bawah / atas sufiks unik (rupiah) untuk transfer manual — tampilan 01–99. */
    public const MANUAL_UNIQUE_SUFFIX_MIN = 1;

    public const MANUAL_UNIQUE_SUFFIX_MAX = 99;

    /**
     * Nominal transfer manual = harga paket (dibulatkan ke rupiah) + sufiks unik 1–99.
     * Menghindari tabrakan dengan pending lain (manual atau Moota) pada nominal yang sama.
     */
    public static function allocateUniqueManualTransferAmount(float $baseAmount): float
    {
        $base = (int) round($baseAmount);

        for ($i = 0; $i < 200; $i++) {
            $suffix = random_int(self::MANUAL_UNIQUE_SUFFIX_MIN, self::MANUAL_UNIQUE_SUFFIX_MAX);
            $candidate = (float) ($base + $suffix);

            if (! static::pendingTransferAmountExists($candidate)) {
                return $candidate;
            }
        }

        return (float) ($base + random_int(1000, 9999));
    }

    /**
     * Sama seperti allocate unik, tetapi sufiks awal deterministik dari order_code order
     * sehingga refresh / GET berulang tidak mengganti nominal selama order sama.
     */
    public static function stableManualTransferAmountForOrder(Order $order, float $baseAmount): float
    {
        $base = (int) round($baseAmount);
        $code = (string) ($order->order_code ?? $order->getKey());
        $start = (int) (abs(crc32($code)) % self::MANUAL_UNIQUE_SUFFIX_MAX) + self::MANUAL_UNIQUE_SUFFIX_MIN;

        $span = self::MANUAL_UNIQUE_SUFFIX_MAX - self::MANUAL_UNIQUE_SUFFIX_MIN + 1;
        for ($i = 0; $i < $span; $i++) {
            $suffix = (($start - self::MANUAL_UNIQUE_SUFFIX_MIN + $i) % $span) + self::MANUAL_UNIQUE_SUFFIX_MIN;
            $candidate = (float) ($base + $suffix);
            if (! static::pendingTransferAmountExists($candidate)) {
                return $candidate;
            }
        }

        return static::allocateUniqueManualTransferAmount((float) $baseAmount);
    }

    /**
     * Nominal unik untuk Moota / QRIS statis (harga paket + sufiks 1–99).
     */
    public static function allocateUniqueMootaTransferAmount(float $baseAmount): float
    {
        $base = (int) round($baseAmount);

        for ($i = 0; $i < 200; $i++) {
            $suffix = random_int(self::MANUAL_UNIQUE_SUFFIX_MIN, self::MANUAL_UNIQUE_SUFFIX_MAX);
            $candidate = (float) ($base + $suffix);

            if (! static::pendingTransferAmountExists($candidate)) {
                return $candidate;
            }
        }

        return (float) ($base + random_int(1000, 9999));
    }

    /**
     * Sama seperti manual stable, tetapi untuk Moota / QRIS:
     * suffix awal deterministik dari order_code agar refresh berulang tetap nominal sama.
     */
    public static function stableMootaTransferAmountForOrder(Order $order, float $baseAmount): float
    {
        $base = (int) round($baseAmount);
        $code = (string) ($order->order_code ?? $order->getKey());
        $start = (int) (abs(crc32($code.'-moota')) % self::MANUAL_UNIQUE_SUFFIX_MAX) + self::MANUAL_UNIQUE_SUFFIX_MIN;

        $span = self::MANUAL_UNIQUE_SUFFIX_MAX - self::MANUAL_UNIQUE_SUFFIX_MIN + 1;
        for ($i = 0; $i < $span; $i++) {
            $suffix = (($start - self::MANUAL_UNIQUE_SUFFIX_MIN + $i) % $span) + self::MANUAL_UNIQUE_SUFFIX_MIN;
            $candidate = (float) ($base + $suffix);
            if (! static::pendingTransferAmountExists($candidate)) {
                return $candidate;
            }
        }

        return static::allocateUniqueMootaTransferAmount((float) $baseAmount);
    }

    /** Apakah nominal sudah dipakai percobaan bayar pending (manual atau Moota). */
    public static function pendingTransferAmountExists(float $amount): bool
    {
        return static::query()
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_SUBMITTED])
            ->where(function ($q) use ($amount) {
                $q->where('moota_transfer_amount', $amount)
                    ->orWhere('manual_transfer_amount', $amount);
            })
            ->exists();
    }

    /** Sufiks 2 digit (01–99) untuk tampilan; null jika tidak relevan atau data lama di luar rentang. */
    public function manualUniqueSuffixFormatted(): ?string
    {
        if ($this->method !== 'manual' || $this->manual_transfer_amount === null) {
            return null;
        }

        $base = (int) round((float) $this->amount);
        $total = (int) round((float) $this->manual_transfer_amount);
        $n = $total - $base;

        if ($n < self::MANUAL_UNIQUE_SUFFIX_MIN || $n > self::MANUAL_UNIQUE_SUFFIX_MAX) {
            return null;
        }

        return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }

    public function getFormattedManualTransferAmountAttribute(): ?string
    {
        if ($this->manual_transfer_amount === null) {
            return null;
        }

        return 'Rp '.number_format((float) $this->manual_transfer_amount, 0, ',', '.');
    }

    /** Rekening Moota dari config (instruksi transfer). */
    public static function getMootaBankInfo(): array
    {
        return [
            'bank_name' => config('moota.bank_name'),
            'account_number' => config('moota.account_number'),
            'account_holder' => config('moota.account_holder'),
        ];
    }

    /** URL QRIS statis dari konfigurasi Moota (null jika tidak diisi). */
    public static function getStaticQrisImageUrl(): ?string
    {
        $url = trim((string) config('moota.static_qris_image_url', ''));
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return asset(ltrim($url, '/'));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_SUBMITTED => __('Submitted'),
            self::STATUS_SUCCESS => __('Success'),
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_EXPIRED => __('Expired'),
            self::STATUS_CANCELLED => __('Cancelled'),
            self::STATUS_VOID => __('Void'),
            self::STATUS_REFUNDED => __('Refunded'),
            default => $this->status,
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getTransferProofUrlAttribute(): ?string
    {
        if (! $this->transfer_proof_path) {
            return null;
        }

        return Storage::disk('public')->exists($this->transfer_proof_path)
            ? Storage::disk('public')->url($this->transfer_proof_path)
            : null;
    }

    /** Rekening manual dari config (untuk tampilan ke user). */
    public static function getManualBankInfo(): array
    {
        return [
            'bank_name' => config('payment.manual.bank_name'),
            'account_number' => config('payment.manual.account_number'),
            'account_holder' => config('payment.manual.account_holder'),
        ];
    }
}

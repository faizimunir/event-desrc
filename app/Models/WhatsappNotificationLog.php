<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WhatsappNotificationLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const TYPE_PAYMENT_LINK = 'payment_link';

    public const TYPE_TICKET_ISSUED = 'ticket_issued';

    public const TYPE_TICKET_RESENT = 'ticket_resent';

    public const TYPE_TRANSFER_PROOF_SUBMITTED = 'transfer_proof_submitted';

    public const TYPE_REGISTRATION_REJECTED = 'registration_rejected';

    public const TYPE_PAYMENT_REJECTED = 'payment_rejected';

    public const TYPE_PAYMENT_EXPIRED = 'payment_expired';

    protected $fillable = [
        'registration_id',
        'type',
        'recipient',
        'status',
        'sent_at',
        'failed_reason',
        'provider_message_id',
        'delivery_status',
        'delivered_at',
        'read_at',
        'scheduled_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /** True when the migration has been applied (avoids errors on stale deploys). */
    public static function tableExists(): bool
    {
        static $exists;

        return $exists ??= Schema::hasTable((new static)->getTable());
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * @param  array{delivery_status?: string|null, delivered_at?: string|null, read_at?: string|null}  $delivery
     */
    public function markSent(?string $providerMessageId = null, array $delivery = []): void
    {
        $attributes = [
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'failed_reason' => null,
        ];

        if (static::supportsDeliveryTracking()) {
            if ($providerMessageId !== null) {
                $attributes['provider_message_id'] = $providerMessageId;
            }

            foreach (['delivery_status', 'delivered_at', 'read_at'] as $key) {
                if (($delivery[$key] ?? null) !== null) {
                    $attributes[$key] = $delivery[$key];
                }
            }
        }

        $this->forceFill($attributes)->save();
    }

    /** Pesan diterima Whacenter, tapi belum dikonfirmasi terkirim (status tetap queued). */
    public function markAccepted(string $providerMessageId): void
    {
        $this->forceFill(['provider_message_id' => $providerMessageId])->save();
    }

    /** Catat jadwal kirim & batas kedaluwarsa agar watchdog bisa mendeteksi pesan yang macet. */
    public static function recordSchedule(int $id, \DateTimeInterface $scheduledAt, \DateTimeInterface $expiresAt): void
    {
        if (! static::supportsScheduleTracking()) {
            return;
        }

        try {
            static::query()->whereKey($id)->update([
                'scheduled_at' => $scheduledAt,
                'expires_at' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** True when the schedule tracking migration has been applied. */
    public static function supportsScheduleTracking(): bool
    {
        static $exists;

        return $exists ??= static::tableExists()
            && Schema::hasColumns((new static)->getTable(), ['scheduled_at', 'expires_at']);
    }

    /** True when the delivery tracking migration has been applied. */
    public static function supportsDeliveryTracking(): bool
    {
        static $exists;

        return $exists ??= static::tableExists()
            && Schema::hasColumns((new static)->getTable(), ['provider_message_id', 'delivery_status', 'delivered_at', 'read_at']);
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failed_reason' => $reason,
        ])->save();
    }

    /** Laravel view name for the message body (e.g. whatsapp.payment-link). */
    public function templateViewName(): string
    {
        return match ($this->type) {
            self::TYPE_PAYMENT_LINK => 'whatsapp.payment-link',
            self::TYPE_TICKET_ISSUED => 'whatsapp.payment-success',
            self::TYPE_TICKET_RESENT => 'whatsapp.payment-success',
            self::TYPE_TRANSFER_PROOF_SUBMITTED => 'whatsapp.transfer-proof-submitted',
            self::TYPE_REGISTRATION_REJECTED => 'whatsapp.registration-rejected',
            self::TYPE_PAYMENT_REJECTED => 'whatsapp.payment-rejected',
            self::TYPE_PAYMENT_EXPIRED => 'whatsapp.payment-expired',
            default => $this->type,
        };
    }

    /** @return array{at: \Illuminate\Support\Carbon, title: string, detail: string|null} */
    public function activityTimelineRow(): array
    {
        $at = match ($this->status) {
            self::STATUS_SENT => $this->sent_at ?? $this->created_at,
            self::STATUS_FAILED => $this->updated_at,
            default => $this->created_at,
        };

        $kind = match ($this->type) {
            self::TYPE_PAYMENT_LINK => __('Payment link'),
            self::TYPE_TICKET_ISSUED => __('E-ticket'),
            self::TYPE_TICKET_RESENT => __('E-ticket (resent)'),
            self::TYPE_TRANSFER_PROOF_SUBMITTED => __('Transfer proof submitted'),
            self::TYPE_REGISTRATION_REJECTED => __('Registration rejected'),
            self::TYPE_PAYMENT_REJECTED => __('Payment rejected'),
            self::TYPE_PAYMENT_EXPIRED => __('Payment expired'),
            default => __('Notification'),
        };

        $deliveryNote = match ($this->delivery_status ?? null) {
            'read' => __('Read'),
            'delivered' => __('Delivered'),
            default => null,
        };

        $title = match ($this->status) {
            self::STATUS_SENT => __('WhatsApp sent: :what', ['what' => $kind]),
            self::STATUS_FAILED => __('WhatsApp failed: :what', ['what' => $kind]),
            default => __('WhatsApp queued: :what', ['what' => $kind]),
        };

        $detailParts = array_filter([
            __('Template: :name', ['name' => $this->templateViewName()]),
            $this->maskedRecipient(),
            $this->status === self::STATUS_SENT ? $deliveryNote : null,
            $this->status === self::STATUS_FAILED && filled($this->failed_reason)
                ? Str::limit($this->failed_reason, 200)
                : null,
        ]);

        return [
            'at' => $at,
            'title' => $title,
            'detail' => $detailParts !== [] ? implode(' · ', $detailParts) : null,
        ];
    }

    public function maskedRecipient(): string
    {
        $r = preg_replace('/\D/', '', $this->recipient) ?? '';
        if (strlen($r) < 6) {
            return '…';
        }

        return substr($r, 0, 4).'…'.substr($r, -4);
    }
}

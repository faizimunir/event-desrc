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

    protected $fillable = [
        'registration_id',
        'type',
        'recipient',
        'status',
        'sent_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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

    public function markSent(): void
    {
        $this->forceFill([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'failed_reason' => null,
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failed_reason' => $reason,
        ])->save();
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
            default => __('Notification'),
        };

        $title = match ($this->status) {
            self::STATUS_SENT => __('WhatsApp sent: :what', ['what' => $kind]),
            self::STATUS_FAILED => __('WhatsApp failed: :what', ['what' => $kind]),
            default => __('WhatsApp queued: :what', ['what' => $kind]),
        };

        $detailParts = array_filter([
            $this->maskedRecipient(),
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

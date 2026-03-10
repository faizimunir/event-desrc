<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    public const CATEGORY_UMUR = 'umur';

    public const CATEGORY_TAHUN = 'tahun';

    /** Draft: default, belum terlihat di halaman utama */
    public const STATUS_DRAFT = 'draft';

    /** Published: terlihat di halaman utama, registrasi belum dibuka */
    public const STATUS_PUBLISHED = 'published';

    /** Open Regist: pendaftaran dibuka */
    public const STATUS_OPEN_REGIST = 'open_regist';

    /** Closed Regist: pendaftaran ditutup */
    public const STATUS_CLOSED_REGIST = 'closed_regist';

    /** Live: event sedang berlangsung */
    public const STATUS_LIVE = 'live';

    /** Done: event telah usai */
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_OPEN_REGIST,
        self::STATUS_CLOSED_REGIST,
        self::STATUS_LIVE,
        self::STATUS_DONE,
    ];

    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'organizer_id',
        'racing_committee_id',
        'master_of_ceremony_id',
        'start_at',
        'end_at',
        'location_id',
        'poster',
        'status',
        'registration_opens_at',
        'registration_closes_at',
    ];

    protected $appends = ['effective_status', 'effective_status_label'];

    public function posterUrl(): ?string
    {
        return $this->poster ? Storage::disk('public')->url($this->poster) : null;
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = static::uniqueSlugFrom($event->title);
            }
        });

        static::updating(function (Event $event) {
            if ($event->isDirty('title') && ! $event->isDirty('slug')) {
                $event->slug = static::uniqueSlugFrom($event->title, $event->id);
            }
        });
    }

    public static function uniqueSlugFrom(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $count = 0;
        while (true) {
            $query = static::query()->where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->doesntExist()) {
                return $slug;
            }
            $count++;
            $slug = $base.'-'.$count;
        }
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function racingCommittee(): BelongsTo
    {
        return $this->belongsTo(RacingCommittee::class, 'racing_committee_id');
    }

    public function masterOfCeremony(): BelongsTo
    {
        return $this->belongsTo(MasterOfCeremony::class, 'master_of_ceremony_id');
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(Bracket::class);
    }

    /**
     * Brackets sorted for public display: by name (alphabetically).
     */
    public function getBracketsSortedForDisplayAttribute(): \Illuminate\Support\Collection
    {
        return $this->brackets->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'event_id')->orderBy('sort_order')->orderBy('id');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class, 'event_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function codeAccess(): HasMany
    {
        return $this->hasMany(EventCodeAccess::class, 'event_id');
    }

    public function isCategoryUmur(): bool
    {
        return $this->category === self::CATEGORY_UMUR;
    }

    public function isClassTahun(): bool
    {
        return $this->category === self::CATEGORY_TAHUN;
    }

    public function scopeVisibleOnHomePage($query)
    {
        return $query->where('status', '!=', self::STATUS_DRAFT);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isOpenRegist(): bool
    {
        return $this->status === self::STATUS_OPEN_REGIST;
    }

    public function isClosedRegist(): bool
    {
        return $this->status === self::STATUS_CLOSED_REGIST;
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_PUBLISHED => __('Published'),
            self::STATUS_OPEN_REGIST => __('Open Regist'),
            self::STATUS_CLOSED_REGIST => __('Closed Regist'),
            self::STATUS_LIVE => __('Live'),
            self::STATUS_DONE => __('Done'),
            default => $this->status,
        };
    }

    /**
     * Status efektif berdasarkan tanggal: otomatis open_regist saat registration_opens_at lewat,
     * otomatis closed_regist saat registration_closes_at lewat.
     */
    public function getEffectiveStatusAttribute(): string
    {
        $now = now();

        if ($this->status === self::STATUS_PUBLISHED
            && $this->registration_opens_at
            && $now->gte($this->registration_opens_at)) {
            return self::STATUS_OPEN_REGIST;
        }

        if ($this->status === self::STATUS_OPEN_REGIST
            && $this->registration_closes_at
            && $now->gte($this->registration_closes_at)) {
            return self::STATUS_CLOSED_REGIST;
        }

        return $this->status;
    }

    public function getEffectiveStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_PUBLISHED => __('Published'),
            self::STATUS_OPEN_REGIST => __('Open Regist'),
            self::STATUS_CLOSED_REGIST => __('Closed Regist'),
            self::STATUS_LIVE => __('Live'),
            self::STATUS_DONE => __('Done'),
            default => $this->effective_status,
        };
    }

    public function isEffectiveDraft(): bool
    {
        return $this->effective_status === self::STATUS_DRAFT;
    }

    public function isEffectivePublished(): bool
    {
        return $this->effective_status === self::STATUS_PUBLISHED;
    }

    public function isEffectiveOpenRegist(): bool
    {
        return $this->effective_status === self::STATUS_OPEN_REGIST;
    }

    public function isEffectiveClosedRegist(): bool
    {
        return $this->effective_status === self::STATUS_CLOSED_REGIST;
    }

    public function isEffectiveLive(): bool
    {
        return $this->effective_status === self::STATUS_LIVE;
    }

    public function isEffectiveDone(): bool
    {
        return $this->effective_status === self::STATUS_DONE;
    }

    /** Apakah pendaftaran sedang dibuka (berdasarkan tanggal). */
    public function isRegistrationOpen(): bool
    {
        if (!$this->registration_opens_at || !$this->registration_closes_at) {
            return $this->isEffectiveOpenRegist();
        }
        $now = now();
        return $now->gte($this->registration_opens_at) && $now->lt($this->registration_closes_at);
    }
}

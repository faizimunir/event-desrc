<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Rundown extends Model
{
    use HasFactory;

    public const TIMING_PENDING = 'pending';

    public const TIMING_LIVE = 'live';

    public const TIMING_ONTIME = 'ontime';

    public const TIMING_DELAYED = 'delayed';

    protected $table = 'event_rundowns';

    protected $fillable = [
        'event_id',
        'start_time',
        'end_time',
        'title',
        'actual_started_at',
        'actual_ended_at',
    ];

    protected function casts(): array
    {
        return [
            'actual_started_at' => 'datetime',
            'actual_ended_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function brackets(): BelongsToMany
    {
        return $this->belongsToMany(Bracket::class, 'event_rundown_bracket', 'event_rundown_id', 'event_bracket_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('event_brackets.id');
    }

    public function formatTime(mixed $time): string
    {
        return Carbon::parse($time)->format('H.i');
    }

    public function formattedTimeRange(): string
    {
        return $this->formatTime($this->start_time).' - '.$this->formatTime($this->end_time);
    }

    public function formattedActualTimeRange(): ?string
    {
        if (! $this->actual_started_at) {
            return null;
        }

        $start = $this->formatTime($this->actual_started_at);
        $end = $this->actual_ended_at ? $this->formatTime($this->actual_ended_at) : '…';

        return $start.' - '.$end;
    }

    /** Label for schedule display: custom title, or compacted bracket names (by sort order). */
    public function displayLabel(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        $names = $this->brackets
            ->sortBy([
                fn ($b) => (int) ($b->pivot->sort_order ?? 0),
                fn ($b) => $b->id,
            ])
            ->pluck('name')
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return '—';
        }

        return static::compactBracketNames($names);
    }

    /**
     * Shorten bracket labels that share year + gender patterns.
     *
     * @param  Collection<int, string>|array<int, string>  $names
     */
    public static function compactBracketNames(Collection|array $names): string
    {
        $names = collect($names)->map(fn ($name) => trim((string) $name))->filter()->values();

        if ($names->isEmpty()) {
            return '—';
        }

        if ($names->count() === 1) {
            return $names->first();
        }

        $parsed = $names->map(function (string $name) {
            if (! preg_match('/^(\d{4})\s+(.+)$/u', $name, $matches)) {
                return null;
            }

            return [
                'year' => (int) $matches[1],
                'gender' => trim($matches[2]),
                'gender_key' => mb_strtolower(trim($matches[2])),
            ];
        });

        if ($parsed->contains(null)) {
            return $names->implode(' & ');
        }

        $years = $parsed->pluck('year')->unique()->sort()->values();
        $genders = $parsed
            ->unique('gender_key')
            ->sortBy(function (array $item) {
                $key = $item['gender_key'];

                return match (true) {
                    str_contains($key, 'boy') => 0,
                    str_contains($key, 'girl') => 1,
                    str_contains($key, 'mix') => 2,
                    default => 3,
                };
            })
            ->values();

        $pairs = $parsed
            ->map(fn (array $item) => $item['year'].'|'.$item['gender_key'])
            ->unique()
            ->values();

        $expected = collect();
        foreach ($years as $year) {
            foreach ($genders as $gender) {
                $expected->push($year.'|'.$gender['gender_key']);
            }
        }

        if ($pairs->count() !== $expected->count() || $pairs->diff($expected)->isNotEmpty()) {
            return $names->implode(' & ');
        }

        $yearLabel = static::formatYearRange($years->all());
        $genderLabel = $genders->pluck('gender')->implode(' & ');

        return trim($yearLabel.' '.$genderLabel);
    }

    /**
     * @param  array<int, int>  $years  sorted unique years
     */
    protected static function formatYearRange(array $years): string
    {
        if ($years === []) {
            return '';
        }

        if (count($years) === 1) {
            return (string) $years[0];
        }

        $isContiguous = true;
        for ($i = 1; $i < count($years); $i++) {
            if ($years[$i] !== $years[$i - 1] + 1) {
                $isContiguous = false;
                break;
            }
        }

        if ($isContiguous) {
            return $years[0].' - '.$years[count($years) - 1];
        }

        return implode(' & ', $years);
    }

    public function timeInputValue(mixed $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }

    public function scheduledStartAt(): ?CarbonInterface
    {
        return $this->scheduleDateTime($this->start_time);
    }

    public function scheduledEndAt(): ?CarbonInterface
    {
        return $this->scheduleDateTime($this->end_time);
    }

    protected function scheduleDateTime(mixed $time): ?CarbonInterface
    {
        if ($time === null || $time === '') {
            return null;
        }

        $this->loadMissing('event');
        $clock = Carbon::parse($time);

        if ($this->event?->start_at) {
            return $this->event->start_at->copy()->setTime(
                $clock->hour,
                $clock->minute,
                $clock->second
            );
        }

        return now()->copy()->setTime(
            $clock->hour,
            $clock->minute,
            $clock->second
        );
    }

    public function isPlaying(): bool
    {
        return $this->actual_started_at !== null && $this->actual_ended_at === null;
    }

    public function isCompleted(): bool
    {
        return $this->actual_started_at !== null && $this->actual_ended_at !== null;
    }

    public function isStartDelayed(): bool
    {
        $scheduled = $this->scheduledStartAt();

        return $scheduled !== null
            && $this->actual_started_at !== null
            && $this->actual_started_at->gt($scheduled);
    }

    public function isEndDelayed(): bool
    {
        $scheduled = $this->scheduledEndAt();

        return $scheduled !== null
            && $this->actual_ended_at !== null
            && $this->actual_ended_at->gt($scheduled);
    }

    /** Overall timing status vs schedule: pending, live, ontime, delayed. */
    public function timingStatus(): string
    {
        if (! $this->actual_started_at) {
            return self::TIMING_PENDING;
        }

        if (! $this->actual_ended_at) {
            return self::TIMING_LIVE;
        }

        if ($this->isStartDelayed() || $this->isEndDelayed()) {
            return self::TIMING_DELAYED;
        }

        return self::TIMING_ONTIME;
    }

    public function timingStatusLabel(): string
    {
        return match ($this->timingStatus()) {
            self::TIMING_LIVE => __('Live'),
            self::TIMING_ONTIME => __('Ontime'),
            self::TIMING_DELAYED => __('Delayed'),
            default => __('Pending'),
        };
    }

    public function play(): void
    {
        $this->forceFill([
            'actual_started_at' => now(),
            'actual_ended_at' => null,
        ])->save();
    }

    public function stop(): void
    {
        if (! $this->actual_started_at) {
            return;
        }

        $this->forceFill([
            'actual_ended_at' => now(),
        ])->save();
    }
}

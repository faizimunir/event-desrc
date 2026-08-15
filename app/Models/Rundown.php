<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Rundown extends Model
{
    use HasFactory;

    protected $table = 'event_rundowns';

    protected $fillable = [
        'event_id',
        'start_time',
        'end_time',
        'title',
    ];

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
     * Examples:
     * - 2018 Boys, 2018 Girls → 2018 Boys & Girls
     * - 2018 Boys, 2018 Girls, 2019 Boys, 2019 Girls → 2018 - 2019 Boys & Girls
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

        // Only compact when every year×gender combination exists (rectangular set).
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
}

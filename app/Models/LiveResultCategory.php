<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class LiveResultCategory extends Model
{
    protected $fillable = [
        'event_id',
        'bracket_id',
        'title',
        'spreadsheet_id',
        'selected_sheets',
        'last_sync',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'selected_sheets' => 'array',
            'is_active' => 'boolean',
            'last_sync' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(Bracket::class, 'bracket_id');
    }

    /**
     * Order by linked bracket's earliest rundown start_time when available,
     * then by title. Categories without a rundown slot sort after those with one.
     */
    public function scopeOrderedByRundown(Builder $query): Builder
    {
        $rundownStartSub = '(
            SELECT MIN(r.start_time)
            FROM event_rundown_bracket AS erb
            INNER JOIN event_rundowns AS r ON r.id = erb.event_rundown_id
            WHERE erb.event_bracket_id = live_result_categories.bracket_id
              AND r.event_id = live_result_categories.event_id
        )';

        $rundownSortSub = '(
            SELECT MIN(erb.sort_order)
            FROM event_rundown_bracket AS erb
            INNER JOIN event_rundowns AS r ON r.id = erb.event_rundown_id
            WHERE erb.event_bracket_id = live_result_categories.bracket_id
              AND r.event_id = live_result_categories.event_id
              AND r.start_time = '.$rundownStartSub.'
        )';

        return $query
            ->orderByRaw("{$rundownStartSub} IS NULL ASC")
            ->orderByRaw("{$rundownStartSub} ASC")
            ->orderByRaw("{$rundownSortSub} IS NULL ASC")
            ->orderByRaw("{$rundownSortSub} ASC")
            ->orderByRaw('LOWER(live_result_categories.title) ASC');
    }

    /** Persist `order` column to match rundown-based display order. */
    public static function syncOrderForEvent(Event $event): void
    {
        $order = 1;
        foreach (static::where('event_id', $event->id)->orderedByRundown()->get() as $category) {
            if ((int) $category->order !== $order) {
                $category->update(['order' => $order]);
            }
            $order++;
        }
    }

    /**
     * Group categories under rundown spacers (time + label).
     *
     * @param  Collection<int, self>  $categories
     * @return Collection<int, array{key: string, header: ?string, categories: Collection<int, self>}>
     */
    public static function groupByRundown(Event $event, Collection $categories): Collection
    {
        if ($categories->isEmpty()) {
            return collect();
        }

        $rundowns = $event->rundowns()->with('brackets')->get();

        /** @var array<int, Rundown> $bracketToRundown */
        $bracketToRundown = [];
        foreach ($rundowns as $rundown) {
            foreach ($rundown->brackets as $bracket) {
                $existing = $bracketToRundown[$bracket->id] ?? null;
                if (! $existing || (string) $rundown->start_time < (string) $existing->start_time) {
                    $bracketToRundown[$bracket->id] = $rundown;
                }
            }
        }

        $groups = collect();
        $assignedIds = [];

        foreach ($rundowns as $rundown) {
            $groupCategories = $categories->filter(function (self $category) use ($rundown, $bracketToRundown) {
                return $category->bracket_id
                    && isset($bracketToRundown[$category->bracket_id])
                    && $bracketToRundown[$category->bracket_id]->id === $rundown->id;
            })
                ->sortBy([
                    function (self $category) use ($rundown) {
                        $bracket = $rundown->brackets->firstWhere('id', $category->bracket_id);

                        return (int) ($bracket?->pivot->sort_order ?? 999);
                    },
                    fn (self $category) => mb_strtolower($category->title),
                ])
                ->values();

            if ($groupCategories->isEmpty()) {
                continue;
            }

            $assignedIds = array_merge($assignedIds, $groupCategories->pluck('id')->all());

            $groups->push([
                'key' => 'rundown-'.$rundown->id,
                'header' => $rundown->formattedTimeRange().' '.$rundown->displayLabel(),
                'categories' => $groupCategories,
            ]);
        }

        $ungrouped = $categories->reject(fn (self $category) => in_array($category->id, $assignedIds, true))->values();

        if ($ungrouped->isNotEmpty()) {
            $groups->push([
                'key' => 'other',
                'header' => $groups->isNotEmpty() ? __('Lainnya') : null,
                'categories' => $ungrouped,
            ]);
        }

        return $groups;
    }
}

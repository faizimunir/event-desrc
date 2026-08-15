<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        return $query
            ->orderByRaw("{$rundownStartSub} IS NULL ASC")
            ->orderByRaw("{$rundownStartSub} ASC")
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
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
            ->withTimestamps();
    }

    public function formatTime(mixed $time): string
    {
        return Carbon::parse($time)->format('H.i');
    }

    public function formattedTimeRange(): string
    {
        return $this->formatTime($this->start_time).' - '.$this->formatTime($this->end_time);
    }

    /** Label for schedule display: custom title, or joined bracket names. */
    public function displayLabel(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        $names = $this->brackets->pluck('name')->filter()->values();

        return $names->isNotEmpty() ? $names->implode(' & ') : '—';
    }

    public function timeInputValue(mixed $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }
}

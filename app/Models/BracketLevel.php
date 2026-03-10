<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BracketLevel extends Model
{
    use HasFactory;

    protected $table = 'event_bracket_levels';

    protected $fillable = [
        'event_bracket_id',
        'event_level_id',
        'name_original',
    ];

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(Bracket::class, 'event_bracket_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'event_level_id');
    }
}

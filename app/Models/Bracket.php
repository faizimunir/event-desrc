<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bracket extends Model
{
    use HasFactory;

    protected $table = 'event_brackets';

    public const GENDER_BOYS = 'boys';

    public const GENDER_GIRLS = 'girls';

    public const RULE_TYPE_AGE = 'age';

    public const RULE_TYPE_BIRTH_YEAR = 'birth_year';

    protected $fillable = [
        'event_id',
        'name',
        'gender_rule',
        'rule_type',
        'birth_year_start',
        'birth_year_end',
        'age_min',
        'age_max',
        'age_ref_date',
        'quota',
    ];

    protected function casts(): array
    {
        return [
            'age_ref_date' => 'date',
            'quota' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bracketLevels(): HasMany
    {
        return $this->hasMany(BracketLevel::class, 'event_bracket_id');
    }

    public function isRuleTypeAge(): bool
    {
        return $this->rule_type === self::RULE_TYPE_AGE;
    }

    public function isRuleTypeBirthYear(): bool
    {
        return $this->rule_type === self::RULE_TYPE_BIRTH_YEAR;
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'bracket_id');
    }

    /** Number of registrations that count toward quota (pending + approved). */
    public function registeredCount(): int
    {
        return $this->registrations()->countsTowardQuota()->count();
    }

    /** Remaining quota (null means unlimited). */
    public function remainingQuota(): ?int
    {
        if ($this->quota === null) {
            return null;
        }
        return max(0, $this->quota - $this->registeredCount());
    }

    /** Whether there is at least one slot left (or unlimited). */
    public function hasQuota(): bool
    {
        $remaining = $this->remainingQuota();
        return $remaining === null || $remaining > 0;
    }
}

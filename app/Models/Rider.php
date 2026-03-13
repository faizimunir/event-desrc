<?php

namespace App\Models;

use App\Models\User;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasMedia;

    protected $fillable = [
        'user_id',
        'name',
        'nickname',
        'pob',
        'dob',
        'gender',
        'number_plate',
        'photo_kia',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_rider');
    }

    /** Age in full years on a given date (default: today). */
    public function ageOn(?\DateTimeInterface $date = null): ?int
    {
        if (! $this->dob) {
            return null;
        }
        $ref = $date ? \Carbon\Carbon::parse($date) : now();
        return $this->dob->diffInYears($ref, false);
    }

    /** Birth year from dob. */
    public function birthYear(): ?int
    {
        return $this->dob?->year;
    }

    /** Display label for gender (boys/girls/other). */
    public function getGenderLabelAttribute(): ?string
    {
        return match ($this->gender) {
            'boys' => __('Boys'),
            'girls' => __('Girls'),
            'other' => __('Other'),
            default => $this->gender,
        };
    }
}

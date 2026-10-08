<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'link',
    ];

    /**
     * User-user (admin organizer) yang berhak mengelola organizer dan event-eventnya.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organizer_user')->withTimestamps();
    }

    /**
     * Apakah user termasuk pengelola organizer ini.
     */
    public function isManagedBy(User $user): bool
    {
        if ($this->relationLoaded('users')) {
            return $this->users->contains('id', $user->id);
        }

        return $this->users()->whereKey($user->id)->exists();
    }

    /**
     * Organizer yang dikelola oleh user tertentu.
     */
    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('users', fn (Builder $q) => $q->whereKey($user->id));
    }

    /**
     * Event-event yang dimiliki organizer ini.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Team-team yang dimiliki organizer ini.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(3)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Organizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'link',
    ];

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(3)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    public function riders(): BelongsToMany
    {
        return $this->belongsToMany(Rider::class, 'organizer_rider')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}

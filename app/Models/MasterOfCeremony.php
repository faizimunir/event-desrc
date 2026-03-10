<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MasterOfCeremony extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'link', 'avatar_mc'];

    public function getAvatarMcUrlAttribute(): ?string
    {
        if (! $this->avatar_mc) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_mc);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(3)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'master_of_ceremony_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    public function eventPackages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'event_package_reward', 'reward_id', 'event_package_id')
            ->withPivot('photo_reward')
            ->withTimestamps();
    }
}

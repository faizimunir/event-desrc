<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'max_participants',
        'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get packages for this category via event_id
     * Package tersedia untuk semua category di event yang sama
     * Menggunakan accessor karena tidak ada foreign key langsung
     */
    public function getPackagesAttribute()
    {
        return Package::where('event_id', $this->event_id)->get();
    }

    /**
     * Get active packages for this category via event_id
     * Method helper untuk mendapatkan active packages
     */
    public function activePackages()
    {
        return Package::where('event_id', $this->event_id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get total quota for this category (from category max_participants)
     */
    public function getTotalQuotaAttribute(): int
    {
        return $this->max_participants ?? 0;
    }

    /**
     * Get total registered participants for this category
     * Count participants who selected this category
     */
    public function getTotalRegisteredAttribute(): int
    {
        return \App\Models\Participant::where('category_id', $this->id)
            ->whereIn('status', ['pending', 'registered', 'confirmed'])
            ->count();
    }

    /**
     * Get participants for this category
     */
    public function participants()
    {
        return $this->hasMany(\App\Models\Participant::class);
    }
}

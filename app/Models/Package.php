<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'max_participants',
        'current_participants',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Package tidak lagi terikat langsung ke category
     * Package tersedia untuk semua category di event yang sama
     * Relasi ini dipertahankan untuk backward compatibility saja
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->whereRaw('1 = 0'); // Always return null
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function isAvailable(): bool
    {
        // Package availability hanya berdasarkan status
        // Kuota diatur di kategori, bukan di paket
        return $this->status === 'active';
    }
}

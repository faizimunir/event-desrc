<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
        'event_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdminEvent(): bool
    {
        return $this->role === 'admin_event';
    }

    public function canAccessEvent($eventId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->event_id == $eventId || $this->createdEvents()->where('id', $eventId)->exists();
    }
}

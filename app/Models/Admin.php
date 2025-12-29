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
        'created_by',
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

    public function eventAccess()
    {
        return $this->belongsToMany(Event::class, 'admin_event_access', 'admin_id', 'event_id')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdminEvent(): bool
    {
        return $this->role === 'admin_event';
    }

    public function isCoAdminEvent(): bool
    {
        return $this->role === 'co_admin_event';
    }

    public function canAccessEvent($eventId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if event was created by this admin
        if ($this->createdEvents()->where('id', $eventId)->exists()) {
            return true;
        }

        // Check single event_id assignment (backward compatibility)
        if ($this->event_id == $eventId) {
            return true;
        }

        // Check multiple event access via pivot table
        if ($this->eventAccess()->where('events.id', $eventId)->exists()) {
            return true;
        }

        return false;
    }

    public function getAccessibleEventIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Event::pluck('id')->toArray();
        }

        $eventIds = [];

        // Add created events
        $eventIds = array_merge($eventIds, $this->createdEvents()->pluck('id')->toArray());

        // Add single event_id if set
        if ($this->event_id) {
            $eventIds[] = $this->event_id;
        }

        // Add events from pivot table
        $eventIds = array_merge($eventIds, $this->eventAccess()->pluck('events.id')->toArray());

        return array_unique($eventIds);
    }
}

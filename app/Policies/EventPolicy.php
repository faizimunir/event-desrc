<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('committee')) {
            return true;
        }
        $organizer = $event->organizer;
        return $organizer && $organizer->user_id !== null && $organizer->user_id === $user->id;
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('committee')) {
            return true;
        }
        $organizer = $event->organizer;
        return $organizer && $organizer->user_id !== null && $organizer->user_id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('committee')) {
            return true;
        }
        $organizer = $event->organizer;
        return $organizer && $organizer->user_id !== null && $organizer->user_id === $user->id;
    }
}

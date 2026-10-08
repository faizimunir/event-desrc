<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        return $this->canManage($user, $event);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->canManage($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->canManage($user, $event);
    }

    /**
     * Admin-level selalu boleh; selain itu user harus termasuk pengelola organizer event.
     */
    private function canManage(User $user, Event $event): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('committee')) {
            return true;
        }

        $organizer = $event->organizer;

        return $organizer !== null && $organizer->isManagedBy($user);
    }
}

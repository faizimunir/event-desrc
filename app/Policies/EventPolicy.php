<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        return $user->canAs('event.update');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->canAs('event.delete');
    }
}

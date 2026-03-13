<?php

namespace App\Policies;

use App\Models\EventCheckin;
use App\Models\User;

class EventCheckinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAs('checkin.read');
    }

    public function view(User $user, EventCheckin $eventCheckin): bool
    {
        return $user->canAs('checkin.read');
    }

    public function create(User $user): bool
    {
        return $user->canAs('checkin.create');
    }

    public function update(User $user, EventCheckin $eventCheckin): bool
    {
        return $user->canAs('checkin.update');
    }

    public function delete(User $user, EventCheckin $eventCheckin): bool
    {
        return $user->canAs('checkin.delete');
    }
}

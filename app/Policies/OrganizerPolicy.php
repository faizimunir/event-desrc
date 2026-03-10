<?php

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function update(User $user, Organizer $organizer): bool
    {
        return $user->canAs('organizer.update');
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        return $user->canAs('organizer.delete');
    }
}

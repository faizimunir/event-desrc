<?php

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function update(User $user, Organizer $organizer): bool
    {
        if ($user->canAs('organizer.update')) {
            return true;
        }
        return $organizer->user_id !== null && $organizer->user_id === $user->id;
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        if ($user->canAs('organizer.delete')) {
            return true;
        }
        return $organizer->user_id !== null && $organizer->user_id === $user->id;
    }
}

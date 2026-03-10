<?php

namespace App\Policies;

use App\Models\Track;
use App\Models\User;

class TrackPolicy
{
    public function update(User $user, Track $track): bool
    {
        return $user->canAs('track.update');
    }

    public function delete(User $user, Track $track): bool
    {
        return $user->canAs('track.delete');
    }
}

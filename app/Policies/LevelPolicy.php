<?php

namespace App\Policies;

use App\Models\Level;
use App\Models\User;

class LevelPolicy
{
    public function update(User $user, Level $level): bool
    {
        return $user->canAs('level.update');
    }

    public function delete(User $user, Level $level): bool
    {
        return $user->canAs('level.delete');
    }
}

<?php

namespace App\Policies;

use App\Models\Rundown;
use App\Models\User;

class RundownPolicy
{
    public function update(User $user, Rundown $rundown): bool
    {
        return $user->canAs('rundown.update');
    }

    public function delete(User $user, Rundown $rundown): bool
    {
        return $user->canAs('rundown.delete');
    }
}

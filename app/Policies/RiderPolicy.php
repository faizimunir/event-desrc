<?php

namespace App\Policies;

use App\Models\Rider;
use App\Models\User;

class RiderPolicy
{
    public function view(User $user, Rider $rider): bool
    {
        return $user->canAs('rider.read');
    }

    public function update(User $user, Rider $rider): bool
    {
        return $user->canAs('rider.update');
    }

    public function delete(User $user, Rider $rider): bool
    {
        return $user->canAs('rider.delete');
    }
}

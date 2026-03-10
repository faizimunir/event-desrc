<?php

namespace App\Policies;

use App\Models\MasterOfCeremony;
use App\Models\User;

class MasterOfCeremonyPolicy
{
    public function update(User $user, MasterOfCeremony $masterOfCeremony): bool
    {
        return $user->canAs('mc.update');
    }

    public function delete(User $user, MasterOfCeremony $masterOfCeremony): bool
    {
        return $user->canAs('mc.delete');
    }
}

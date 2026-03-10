<?php

namespace App\Policies;

use App\Models\RacingCommittee;
use App\Models\User;

class RacingCommitteePolicy
{
    public function update(User $user, RacingCommittee $racingCommittee): bool
    {
        return $user->canAs('rc.update');
    }

    public function delete(User $user, RacingCommittee $racingCommittee): bool
    {
        return $user->canAs('rc.delete');
    }
}

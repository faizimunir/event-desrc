<?php

namespace App\Policies;

use App\Models\Reward;
use App\Models\User;

class RewardPolicy
{
    public function update(User $user, Reward $reward): bool
    {
        return $user->canAs('reward.update');
    }

    public function delete(User $user, Reward $reward): bool
    {
        return $user->canAs('reward.delete');
    }
}

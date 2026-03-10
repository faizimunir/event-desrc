<?php

namespace App\Policies;

use App\Models\BracketLevel;
use App\Models\User;

class BracketLevelPolicy
{
    public function update(User $user, BracketLevel $bracketLevel): bool
    {
        return $user->canAs('bracket_level.update');
    }

    public function delete(User $user, BracketLevel $bracketLevel): bool
    {
        return $user->canAs('bracket_level.delete');
    }
}

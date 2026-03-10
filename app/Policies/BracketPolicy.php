<?php

namespace App\Policies;

use App\Models\Bracket;
use App\Models\User;

class BracketPolicy
{
    public function update(User $user, Bracket $bracket): bool
    {
        return $user->canAs('bracket.update');
    }

    public function delete(User $user, Bracket $bracket): bool
    {
        return $user->canAs('bracket.delete');
    }
}

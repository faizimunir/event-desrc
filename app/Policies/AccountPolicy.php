<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function update(User $user, Account $account): bool
    {
        return $user->canAs('account.update');
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->canAs('account.delete');
    }
}

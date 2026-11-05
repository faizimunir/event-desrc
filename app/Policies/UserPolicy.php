<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $model): bool
    {
        return $user->canAs('user.read');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->canAs('user.update')) {
            return false;
        }

        // Hanya super_admin yang boleh mengubah akun super_admin lain.
        if ($model->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if (! $user->canAs('user.delete')) {
            return false;
        }

        if ($model->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }
}

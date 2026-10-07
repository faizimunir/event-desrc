<?php

namespace App\Policies;

use App\Models\Rider;
use App\Models\User;

class RiderPolicy
{
    public function view(User $user, Rider $rider): bool
    {
        return $user->canAs('rider.read') || $this->ownsAsMember($user, $rider);
    }

    public function update(User $user, Rider $rider): bool
    {
        return $user->canAs('rider.update') || $this->ownsAsMember($user, $rider);
    }

    public function delete(User $user, Rider $rider): bool
    {
        return $user->canAs('rider.delete');
    }

    /** Pemilik rider dengan hak My Rider boleh melihat & mengubah rider miliknya (tanpa hak hapus). */
    private function ownsAsMember(User $user, Rider $rider): bool
    {
        return $rider->user_id !== null
            && $rider->user_id === $user->id
            && $user->canAs('myrider.manage');
    }
}

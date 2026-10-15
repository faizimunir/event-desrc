<?php

namespace App\Models\Concerns;

use App\Exceptions\ComponentHasRegistrationsException;

/**
 * Pengaman penghapusan untuk model yang punya relasi `registrations()` dengan foreign key cascade
 * (Bracket, Package). Data pendaftar bersifat sensitif, jadi model tidak boleh dihapus selama masih
 * ada registrasi di dalamnya, kecuali bypass dinyalakan secara eksplisit (hanya super_admin).
 *
 * Catatan: guard ini hanya aktif untuk $model->delete(); cascade dari database (mis. hapus Event)
 * tidak melewati event model.
 */
trait GuardsDeletionWithRegistrations
{
    /** Hanya diset true oleh alur "force delete" super_admin. */
    public bool $allowDeleteWithRegistrations = false;

    public static function bootGuardsDeletionWithRegistrations(): void
    {
        static::deleting(function (self $model): void {
            if ($model->allowDeleteWithRegistrations) {
                return;
            }

            $count = $model->registrationsCount();
            if ($count > 0) {
                throw new ComponentHasRegistrationsException($count);
            }
        });
    }

    /** Semua status dihitung (termasuk cancelled/rejected) karena semuanya ikut terhapus oleh cascade. */
    public function registrationsCount(): int
    {
        return $this->registrations()->count();
    }

    public function hasRegistrations(): bool
    {
        return $this->registrations()->exists();
    }
}

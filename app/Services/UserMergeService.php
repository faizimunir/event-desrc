<?php

namespace App\Services;

use App\Models\EventCheckin;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class UserMergeService
{
    /**
     * Gabungkan beberapa akun user: semua relasi yang mereferensi user sumber
     * diarahkan ke $primaryUserId, peran digabung ke akun utama, lalu user sumber dihapus.
     *
     * @param  list<int>  $userIds
     */
    public function mergeIntoPrimary(array $userIds, int $primaryUserId): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $primaryUserId = (int) $primaryUserId;

        if (count($userIds) < 2) {
            throw new \InvalidArgumentException(__('Select at least two users to merge.'));
        }

        if (! in_array($primaryUserId, $userIds, true)) {
            throw new \InvalidArgumentException(__('The primary account must be one of the selected users.'));
        }

        $secondaryIds = array_values(array_diff($userIds, [$primaryUserId]));

        DB::transaction(function () use ($userIds, $primaryUserId, $secondaryIds) {
            $users = User::query()
                ->whereIn('id', $userIds)
                ->with('roles')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($users->count() !== count($userIds)) {
                throw new \RuntimeException(__('One or more users no longer exist.'));
            }

            $primary = $users->firstWhere('id', $primaryUserId);
            if (! $primary instanceof User) {
                throw new \RuntimeException(__('Primary user not found.'));
            }

            $allRoleNames = $users
                ->flatMap(fn (User $u) => $u->roles->pluck('name'))
                ->unique()
                ->values()
                ->all();

            $primary->syncRoles($allRoleNames);

            foreach ($secondaryIds as $sid) {
                Rider::query()->where('user_id', $sid)->update(['user_id' => $primaryUserId]);
                Order::query()->where('user_id', $sid)->update(['user_id' => $primaryUserId]);
                Organizer::query()->where('user_id', $sid)->update(['user_id' => $primaryUserId]);
                Payment::query()->where('reviewed_by', $sid)->update(['reviewed_by' => $primaryUserId]);
                EventCheckin::query()->where('checked_in_by', $sid)->update(['checked_in_by' => $primaryUserId]);
                DB::table('sessions')->where('user_id', $sid)->update(['user_id' => $primaryUserId]);
            }

            $this->reassignDirectPermissions($primaryUserId, $secondaryIds);

            foreach ($secondaryIds as $sid) {
                $secondary = $users->firstWhere('id', $sid);
                if (! $secondary instanceof User) {
                    continue;
                }
                $secondary->syncRoles([]);
                $secondary->delete();
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * @param  list<int>  $secondaryIds
     */
    private function reassignDirectPermissions(int $primaryUserId, array $secondaryIds): void
    {
        if ($secondaryIds === []) {
            return;
        }

        $table = config('permission.table_names.model_has_permissions');
        $modelType = User::class;

        $rows = DB::table($table)
            ->where('model_type', $modelType)
            ->whereIn('model_id', $secondaryIds)
            ->get();

        foreach ($rows as $row) {
            $existsOnPrimary = DB::table($table)
                ->where('model_type', $modelType)
                ->where('model_id', $primaryUserId)
                ->where('permission_id', $row->permission_id)
                ->exists();

            if ($existsOnPrimary) {
                DB::table($table)
                    ->where('model_type', $modelType)
                    ->where('model_id', $row->model_id)
                    ->where('permission_id', $row->permission_id)
                    ->delete();
            } else {
                DB::table($table)
                    ->where('model_type', $modelType)
                    ->where('model_id', $row->model_id)
                    ->where('permission_id', $row->permission_id)
                    ->update(['model_id' => $primaryUserId]);
            }
        }
    }
}

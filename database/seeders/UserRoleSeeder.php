<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role ada
        $roles = [
            'super_admin',
            'admin',
            'organizer',
            'committee',
            'member',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $users = [
            [
                'name'  => 'Super Eljo',
                'email' => 'supereljo@desrc.id',
                'roles' => ['super_admin'],
            ],
            [
                'name'  => 'Eljo',
                'email' => 'eljo@desrc.id',
                'roles' => ['admin', 'committee'],
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'         => $data['name'],
                    'password'     => Hash::make('password'),
                    'activated_at' => now(),
                ]
            );

            if ($user->activated_at === null) {
                $user->update(['activated_at' => now()]);
            }

            $user->syncRoles($data['roles']);
        }
    }
}

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
                'name'  => 'Windra',
                'email' => 'windra@example.com',
                'roles' => ['super_admin'],
            ],
            [
                'name'  => 'Said',
                'email' => 'said@example.com',
                'roles' => ['admin', 'committee'], // Multi-role: admin + committee
            ],
            [
                'name'  => 'Nisa',
                'email' => 'nisa@example.com',
                'roles' => ['member'],
            ],
            [
                'name'  => 'Andry',
                'email' => 'andry@example.com',
                'roles' => ['organizer'],
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

<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@eventdesrc.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role' => 'super_admin',
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'event@eventdesrc.com'],
            [
                'name' => 'Admin Event',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role' => 'admin_event',
            ]
        );
    }
}

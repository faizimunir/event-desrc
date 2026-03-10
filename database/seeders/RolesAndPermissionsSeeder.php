<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'rider.create','rider.read','rider.update','rider.delete',
            'event.create','event.read','event.update','event.delete',
            'bracket.create','bracket.read','bracket.update','bracket.delete',
            'bracket_level.create','bracket_level.read','bracket_level.update','bracket_level.delete',
            'package.create','package.read','package.update','package.delete',
            'location.create','location.read','location.update','location.delete',
            'organizer.create','organizer.read','organizer.update','organizer.delete',
            'rc.create','rc.read','rc.update','rc.delete',
            'level.create','level.read','level.update','level.delete',
            'mc.create','mc.read','mc.update','mc.delete',
            'user.create','user.read','user.update','user.delete',
            'track.create','track.read','track.update','track.delete',
            'reward.create','reward.read','reward.update','reward.delete',
        ];

        $guard = config('auth.defaults.guard');

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $admin      = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $organizer  = Role::firstOrCreate(['name' => 'organizer', 'guard_name' => $guard]);
        $committee  = Role::firstOrCreate(['name' => 'committee', 'guard_name' => $guard]);
        $member     = Role::firstOrCreate(['name' => 'member', 'guard_name' => $guard]);

        $superAdmin->syncPermissions(Permission::all());
        $admin->syncPermissions([
            'rider.create','rider.read','rider.update','rider.delete',
            'event.create','event.read','event.update','event.delete',
            'bracket.create','bracket.read','bracket.update','bracket.delete',
            'bracket_level.create','bracket_level.read','bracket_level.update','bracket_level.delete',
            'package.create','package.read','package.update','package.delete',
            'location.create','location.read','location.update','location.delete',
            'organizer.create','organizer.read','organizer.update','organizer.delete',
            'rc.create','rc.read','rc.update','rc.delete',
            'level.create','level.read','level.update','level.delete',
            'mc.create','mc.read','mc.update','mc.delete',
            'track.create','track.read','track.update','track.delete',
            'reward.create','reward.read','reward.update','reward.delete',
        ]);
        $organizer->syncPermissions([
            'event.create','event.read','event.update','event.delete',
            'bracket.create','bracket.read','bracket.update','bracket.delete',
            'bracket_level.create','bracket_level.read','bracket_level.update','bracket_level.delete',
            'package.create','package.read','package.update','package.delete',
            'location.create','location.read','location.update','location.delete',
            'organizer.create','organizer.read','organizer.update','organizer.delete',
            'rc.create','rc.read','rc.update','rc.delete',
            'level.create','level.read','level.update','level.delete',
            'mc.create','mc.read','mc.update','mc.delete',
            'track.create','track.read','track.update','track.delete',
            'reward.create','reward.read','reward.update','reward.delete',
        ]);
        $committee->syncPermissions([
            'event.create','event.read','event.update','event.delete',
            'bracket.create','bracket.read','bracket.update','bracket.delete',
            'bracket_level.create','bracket_level.read','bracket_level.update','bracket_level.delete',
            'package.create','package.read','package.update','package.delete',
            'location.create','location.read','location.update','location.delete',
            'organizer.create','organizer.read','organizer.update','organizer.delete',
            'rc.create','rc.read','rc.update','rc.delete',
            'level.create','level.read','level.update','level.delete',
            'mc.create','mc.read','mc.update','mc.delete',
            'track.create','track.read','track.update','track.delete',
            'reward.create','reward.read','reward.update','reward.delete',
        ]);
        $member->syncPermissions(['rider.read','event.read','location.read','organizer.read','rc.read','bracket.read','bracket_level.read','package.read','level.read','mc.read','track.read','reward.read']);
    }
}

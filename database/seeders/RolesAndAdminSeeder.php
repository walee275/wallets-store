<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['owner', 'staff', 'support'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@commerce.test'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('password'),
                'type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['owner']);

        User::query()->updateOrCreate(
            ['email' => 'customer@commerce.test'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('password'),
                'type' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}

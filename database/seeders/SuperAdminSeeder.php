<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Super Admin Role
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'System wide administrator',
            ]
        );

        // 2. Create Super Admin User
        $user = User::firstOrCreate(
            ['email' => 'superadmin@nubixlabs.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password123'),
                'role_id' => $role->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        $this->command->info("Super Admin created: {$user->email} / password123");
    }
}

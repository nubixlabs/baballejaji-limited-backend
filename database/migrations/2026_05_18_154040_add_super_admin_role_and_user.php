<?php

use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Create super_admin role if it doesn't exist
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
            ]
        );

        // Create superadmin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'superadmin@nubixlabs.com'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@nubixlabs.com',
                'password' => Hash::make('password123'),
                'role_id' => $superAdminRole->id,
                'username' => 'superadmin',
                'phone' => '08000000000',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        User::where('email', 'superadmin@nubixlabs.com')->delete();
        Role::where('name', 'super_admin')->delete();
    }
};

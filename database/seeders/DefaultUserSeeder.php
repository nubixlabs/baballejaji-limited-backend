<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the filling station role
        $fillingStationRole = Role::where('name', 'filling_station')->first();
        
        if (!$fillingStationRole) {
            $this->command->error('Filling station role not found. Please run RoleSeeder first.');
            return;
        }

        // Create a default filling station user if it doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'admin@fillingstation.com'],
            [
                'name' => 'Filling Station Admin',
                'email' => 'admin@fillingstation.com',
                'password' => Hash::make('password123'),
                'role_id' => $fillingStationRole->id,
                'username' => 'admin',
                'phone' => '08123456789',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Default user created: {$user->email} (password: password123)");
    }
}
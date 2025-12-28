<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
            AccountSeeder::class,
            SettingSeeder::class,
            UserGroupSeeder::class,
            DefaultUserSeeder::class,
            
            // Additional seeders for complete database
            CustomerSeeder::class,
            DepartmentSeeder::class,
            LevelSeeder::class,
            LocationSeeder::class,
            TankGroupSeeder::class,
            TankSeeder::class,
            NozzleSeeder::class,
            // StaffSeeder::class,  // TODO: Fix column mismatch issue
            FuelTicketSeeder::class,
        ]);

        // Create test user without role for development
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}

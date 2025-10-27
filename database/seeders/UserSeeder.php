<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $fillingStationRole = Role::where('name', 'filling_station')->first();
        $transportRole = Role::where('name', 'transport')->first();
        $sparePartsRole = Role::where('name', 'spare_parts')->first();

        // Create sample users for each role
        $users = [
            // Filling Station Users
            [
                'name' => 'Total Filling Station',
                'email' => 'fillingstation@baballejaji.com',
                'password' => Hash::make('password123'),
                'role_id' => $fillingStationRole->id,
            ],
            [
                'name' => 'Oando Station Manager',
                'email' => 'oando@baballejaji.com',
                'password' => Hash::make('password123'),
                'role_id' => $fillingStationRole->id,
            ],

            // Transport Users
            [
                'name' => 'ABC Transport',
                'email' => 'transport@baballejaji.com',
                'password' => Hash::make('password123'),
                'role_id' => $transportRole->id,
            ],
            [
                'name' => 'XYZ Logistics',
                'email' => 'logistics@baballejaji.com',
                'password' => Hash::make('password123'),
                'role_id' => $transportRole->id,
            ],

            // Spare Parts Users
            [
                'name' => 'Auto Parts Pro',
                'email' => 'spareparts@baballejaji.com',
                'password' => Hash::make('password123'),
                'role_id' => $sparePartsRole->id,
            ],
            [
                'name' => 'Car Parts Hub',
                'email' => 'carparts@baballejaji.com',
                'password' => Hash::make('password123'),
                'role_id' => $sparePartsRole->id,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
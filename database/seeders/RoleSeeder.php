<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'filling_station',
                'display_name' => 'Filling Station',
                'description' => 'Users who manage filling stations',
            ],
            [
                'name' => 'transport',
                'display_name' => 'Transport',
                'description' => 'Users who manage transportation',
            ],
            [
                'name' => 'spare_parts',
                'display_name' => 'Spare Parts',
                'description' => 'Users who manage spare parts',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']], // check this unique column first
                $role // create if not found
            );
        }
    }
}

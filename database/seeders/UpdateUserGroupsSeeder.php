<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserGroup;

class UpdateUserGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Filling Station', 'description' => 'Users for filling station management'],
            ['name' => 'Head Office', 'description' => 'Head office administrative users'],
            ['name' => 'Spare Parts', 'description' => 'Users for spare parts management'],
            ['name' => 'Transport', 'description' => 'Users for transport management'],
        ];

        foreach ($groups as $group) {
            UserGroup::firstOrCreate(
                ['name' => $group['name']],
                $group
            );
        }
    }
}

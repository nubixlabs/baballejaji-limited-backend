<?php

namespace Database\Seeders;

use App\Models\TankGroup;
use Illuminate\Database\Seeder;

class TankGroupSeeder extends Seeder
{
    public function run(): void
    {
        $tankGroups = [
            [
                'name' => 'PRODUCT TANKS',
                'description' => 'Main fuel storage tanks for products',
            ],
            [
                'name' => 'RESERVE TANKS',
                'description' => 'Reserve fuel storage tanks',
            ],
        ];

        foreach ($tankGroups as $group) {
            TankGroup::create($group);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TankGroup;
use App\Models\FillingStation;
use Illuminate\Database\Seeder;

class TankGroupSeeder extends Seeder
{
    public function run(): void
    {
        $stationId = FillingStation::value('id');
        if (!$stationId) {
            $this->command->warn('No filling station found. Skipping TankGroupSeeder.');
            return;
        }

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
            TankGroup::create(array_merge($group, ['filling_station_id' => $stationId]));
        }
    }
}

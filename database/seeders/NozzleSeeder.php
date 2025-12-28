<?php

namespace Database\Seeders;

use App\Models\Nozzle;
use App\Models\Tank;
use App\Models\Product;
use Illuminate\Database\Seeder;

class NozzleSeeder extends Seeder
{
    public function run(): void
    {
        $pmsTanks = Tank::whereHas('product', function ($q) {
            $q->where('code', 'PMS');
        })->get();

        $agoTanks = Tank::whereHas('product', function ($q) {
            $q->where('code', 'AGO');
        })->get();

        $nozzles = [];

        // PMS Nozzles
        if ($pmsTanks->isNotEmpty()) {
            $tankId = $pmsTanks->first()->id;
            $nozzles = array_merge($nozzles, [
                [
                    'name' => 'Nozzle 1',
                    'tank_id' => $tankId,
                    'reading' => 0,
                    'status' => 'Active',
                    'type' => 'Standard',
                    'dispenser_type' => 'Digital',
                    'is_online' => true,
                ],
                [
                    'name' => 'Nozzle 2',
                    'tank_id' => $tankId,
                    'reading' => 0,
                    'status' => 'Active',
                    'type' => 'Standard',
                    'dispenser_type' => 'Digital',
                    'is_online' => true,
                ],
                [
                    'name' => 'Nozzle 3',
                    'tank_id' => $tankId,
                    'reading' => 0,
                    'status' => 'Active',
                    'type' => 'Standard',
                    'dispenser_type' => 'Digital',
                    'is_online' => true,
                ],
                [
                    'name' => 'Nozzle 4',
                    'tank_id' => $tankId,
                    'reading' => 0,
                    'status' => 'Active',
                    'type' => 'Standard',
                    'dispenser_type' => 'Digital',
                    'is_online' => true,
                ],
            ]);
        }

        // AGO Nozzles
        if ($agoTanks->isNotEmpty()) {
            $tankId = $agoTanks->first()->id;
            $nozzles = array_merge($nozzles, [
                [
                    'name' => 'Nozzle 5',
                    'tank_id' => $tankId,
                    'reading' => 0,
                    'status' => 'Active',
                    'type' => 'Standard',
                    'dispenser_type' => 'Digital',
                    'is_online' => true,
                ],
                [
                    'name' => 'Nozzle 6',
                    'tank_id' => $tankId,
                    'reading' => 0,
                    'status' => 'Active',
                    'type' => 'Standard',
                    'dispenser_type' => 'Digital',
                    'is_online' => true,
                ],
            ]);
        }

        foreach ($nozzles as $nozzle) {
            Nozzle::create($nozzle);
        }
    }
}

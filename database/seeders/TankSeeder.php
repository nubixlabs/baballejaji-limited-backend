<?php

namespace Database\Seeders;

use App\Models\Tank;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TankSeeder extends Seeder
{
    public function run(): void
    {
        $pmsProduct = Product::where('code', 'PMS')->first();
        $agoProduct = Product::where('code', 'AGO')->first();
        $dpkProduct = Product::where('code', 'DPK')->first();

        $tanks = [
            [
                'name' => 'Tank 1 - PMS',
                'product_id' => $pmsProduct?->id,
                'capacity' => 50000,
                'content' => 35000,
                'level' => 70.00, // 35000/50000 * 100
                'atg_status' => 'Online',
                'group' => 'PRODUCT TANKS',
            ],
            [
                'name' => 'Tank 2 - PMS',
                'product_id' => $pmsProduct?->id,
                'capacity' => 50000,
                'content' => 42000,
                'level' => 84.00, // 42000/50000 * 100
                'atg_status' => 'Online',
                'group' => 'PRODUCT TANKS',
            ],
            [
                'name' => 'Tank 3 - AGO',
                'product_id' => $agoProduct?->id,
                'capacity' => 45000,
                'content' => 28000,
                'level' => 62.22, // 28000/45000 * 100
                'atg_status' => 'Online',
                'group' => 'PRODUCT TANKS',
            ],
            [
                'name' => 'Tank 4 - AGO',
                'product_id' => $agoProduct?->id,
                'capacity' => 45000,
                'content' => 38000,
                'level' => 84.44, // 38000/45000 * 100
                'atg_status' => 'Online',
                'group' => 'PRODUCT TANKS',
            ],
            [
                'name' => 'Tank 5 - DPK',
                'product_id' => $dpkProduct?->id,
                'capacity' => 30000,
                'content' => 15000,
                'level' => 50.00, // 15000/30000 * 100
                'atg_status' => 'Online',
                'group' => 'PRODUCT TANKS',
            ],
        ];

        foreach ($tanks as $tank) {
            if ($tank['product_id']) {
                Tank::create($tank);
            }
        }
    }
}

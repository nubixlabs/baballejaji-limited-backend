<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'PMS',
                'name' => 'Premium Motor Spirit (Petrol)',
                'si_unit' => 'Litres',
                'quantity' => 50000.00,
                'cost_price' => 580.00,
                'retail_price' => 650.00,
                'dealer_price' => 620.00,
                'bulk_price' => 600.00,
                're_order_level' => 5000.00,
                'iot_product' => 'PMS_TANK_01',
                'created_by' => 1,
                'last_modified_by' => 1,
            ],
            [
                'code' => 'AGO',
                'name' => 'Automotive Gas Oil (Diesel)',
                'si_unit' => 'Litres',
                'quantity' => 75000.00,
                'cost_price' => 1050.00,
                'retail_price' => 1150.00,
                'dealer_price' => 1120.00,
                'bulk_price' => 1100.00,
                're_order_level' => 8000.00,
                'iot_product' => 'AGO_TANK_01',
                'created_by' => 1,
                'last_modified_by' => 1,
            ],
            [
                'code' => 'DPK',
                'name' => 'Dual Purpose Kerosene',
                'si_unit' => 'Litres',
                'quantity' => 25000.00,
                'cost_price' => 750.00,
                'retail_price' => 850.00,
                'dealer_price' => 820.00,
                'bulk_price' => 800.00,
                're_order_level' => 3000.00,
                'iot_product' => 'DPK_TANK_01',
                'created_by' => 1,
                'last_modified_by' => 1,
            ],
            [
                'code' => 'LPG',
                'name' => 'Liquefied Petroleum Gas',
                'si_unit' => 'Kg',
                'quantity' => 5000.00,
                'cost_price' => 800.00,
                'retail_price' => 900.00,
                'dealer_price' => 870.00,
                'bulk_price' => 850.00,
                're_order_level' => 500.00,
                'iot_product' => 'LPG_TANK_01',
                'created_by' => 1,
                'last_modified_by' => 1,
            ],
            [
                'code' => 'LUBRICANT',
                'name' => 'Engine Oil & Lubricants',
                'si_unit' => 'Litres',
                'quantity' => 2000.00,
                'cost_price' => 3500.00,
                'retail_price' => 4200.00,
                'dealer_price' => 4000.00,
                'bulk_price' => 3800.00,
                're_order_level' => 200.00,
                'iot_product' => null,
                'created_by' => 1,
                'last_modified_by' => 1,
            ],
            [
                'code' => 'ADDITIVE',
                'name' => 'Fuel Additives',
                'si_unit' => 'Litres',
                'quantity' => 500.00,
                'cost_price' => 2000.00,
                'retail_price' => 2500.00,
                'dealer_price' => 2300.00,
                'bulk_price' => 2200.00,
                're_order_level' => 50.00,
                'iot_product' => null,
                'created_by' => 1,
                'last_modified_by' => 1,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['code' => $productData['code']],
                $productData
            );
        }

        $this->command->info('Products seeded successfully!');
    }
}
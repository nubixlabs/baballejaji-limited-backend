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
                'name' => 'Petrol',
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
                'name' => 'Diesel',
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
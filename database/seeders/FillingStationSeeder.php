<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Tank;
use App\Models\Customer;
use App\Models\Nozzle;
use App\Models\TankGroup;
use App\Models\FillingStation;

class FillingStationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create filling station
        $station = FillingStation::create([
            'name' => 'Main Station',
            'code' => 'MAIN001',
            'address' => '123 Main Street, Lagos, Nigeria',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'phone' => '+234-800-000-0001',
            'email' => 'main@baballejaji.com',
            'is_active' => true,
        ]);

        // Create tank groups
        $tankGroups = [
            ['name' => 'PMS Group', 'description' => 'Petrol tanks'],
            ['name' => 'AGO Group', 'description' => 'Diesel tanks'],
        ];

        foreach ($tankGroups as $group) {
            TankGroup::create($group);
        }

        // Create products
        $products = [
            [
                'code' => 'PMS',
                'name' => 'Premium Motor Spirit (Petrol)',
                'si_unit' => 'Litres',
                'quantity' => 50000,
                'cost_price' => 165.50,
                'retail_price' => 185.00,
                'dealer_price' => 180.00,
                'bulk_price' => 175.00,
                're_order_level' => 5000,
                'category' => 'Fuel',
                'based_on' => 'Litres',
            ],
            [
                'code' => 'AGO',
                'name' => 'Automotive Gas Oil (Diesel)',
                'si_unit' => 'Litres',
                'quantity' => 30000,
                'cost_price' => 155.00,
                'retail_price' => 175.00,
                'dealer_price' => 170.00,
                'bulk_price' => 165.00,
                're_order_level' => 3000,
                'category' => 'Fuel',
                'based_on' => 'Litres',
            ],
            [
                'code' => 'DPK',
                'name' => 'Dual Purpose Kerosene',
                'si_unit' => 'Litres',
                'quantity' => 15000,
                'cost_price' => 125.00,
                'retail_price' => 145.00,
                'dealer_price' => 140.00,
                'bulk_price' => 135.00,
                're_order_level' => 2000,
                'category' => 'Fuel',
                'based_on' => 'Litres',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Create tanks
        $tanks = [
            ['name' => 'PMS Tank 1', 'capacity' => 45000, 'product_id' => 1, 'group' => 'PMS Group', 'atg_status' => 'Online', 'filling_station_id' => $station->id],
            ['name' => 'PMS Tank 2', 'capacity' => 45000, 'product_id' => 1, 'group' => 'PMS Group', 'atg_status' => 'Online', 'filling_station_id' => $station->id],
            ['name' => 'AGO Tank 1', 'capacity' => 35000, 'product_id' => 2, 'group' => 'AGO Group', 'atg_status' => 'Online', 'filling_station_id' => $station->id],
            ['name' => 'DPK Tank 1', 'capacity' => 20000, 'product_id' => 3, 'group' => 'Kerosene Group', 'atg_status' => 'Online', 'filling_station_id' => $station->id],
        ];

        foreach ($tanks as $tank) {
            Tank::create($tank);
        }

        // Create nozzles for each tank
        $nozzleCount = 1;
        foreach ($tanks as $tank) {
            for ($i = 1; $i <= 3; $i++) {
                Nozzle::create([
                    'name' => "Nozzle {$nozzleCount}",
                    'tank_id' => Tank::where('name', $tank['name'])->first()->id,
                    'description' => "Nozzle for {$tank['name']}",
                    'status' => 'Active',
                    'reading' => rand(1000, 5000),
                    'type' => 'Standard',
                    'dispenser_type' => 'Wayne',
                    'is_online' => true,
                    'filling_station_id' => $station->id,
                ]);
                $nozzleCount++;
            }
        }

        // Create customers
        $customers = [
            [
                'name' => 'John Doe',
                'contact_person' => 'John Doe',
                'phone_number' => '+234-800-000-0001',
                'email' => 'john.doe@email.com',
                'address' => '123 Customer Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'credit_limit' => 100000,
                'credit_balance' => 0,
                'customer_type' => 'retail',
            ],
            [
                'company' => 'ABC Transport Ltd',
                'contact_person' => 'Jane Smith',
                'phone_number' => '+234-800-000-0002',
                'email' => 'fleet@abctransport.com',
                'address' => '456 Fleet Avenue',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'credit_limit' => 5000000,
                'credit_balance' => 0,
                'customer_type' => 'bulk',
            ],
            [
                'name' => 'Ahmed Ibrahim',
                'contact_person' => 'Ahmed Ibrahim',
                'phone_number' => '+234-800-000-0003',
                'email' => 'ahmed.ibrahim@email.com',
                'address' => '789 Local Road',
                'city' => 'Kano',
                'state' => 'Kano',
                'country' => 'Nigeria',
                'credit_limit' => 50000,
                'credit_balance' => 0,
                'customer_type' => 'retail',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        $this->command->info('Filling station seed data created successfully!');
    }
}

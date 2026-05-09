<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TankDipping;
use App\Models\Tank;

class TankDippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tanks
        $tanks = Tank::all();
        
        if ($tanks->isEmpty()) {
            $this->command->info('No tanks found. Please run FillingStationSeeder first.');
            return;
        }

        // Create sample dippings for each tank
        foreach ($tanks as $tank) {
            // Create 5-10 dippings per tank for the last 30 days
            $dippingCount = rand(5, 10);
            
            for ($i = 0; $i < $dippingCount; $i++) {
                $dippingDate = now()->subDays(rand(0, 30));
                $dippedQuantity = rand(1000, $tank->capacity * 0.8);
                $atgQuantity = $dippedQuantity + rand(-50, 50); // Small variance
                
                TankDipping::create([
                    'tank_id' => $tank->id,
                    'dipping_date' => $dippingDate->format('Y-m-d'),
                    'dipped_quantity' => $dippedQuantity,
                    'atg_quantity' => $atgQuantity,
                    'variance' => abs($dippedQuantity - $atgQuantity),
                    'notes' => 'Regular dipping check',
                    'created_by' => 1,
                    'filling_station_id' => $tank->filling_station_id,
                ]);
            }
        }

        $this->command->info('Tank dipping seed data created successfully!');
    }
}

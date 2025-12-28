<?php

namespace Database\Seeders;

use App\Models\FuelTicket;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FuelTicketSeeder extends Seeder
{
    public function run(): void
    {
        $pmsProduct = Product::where('code', 'PMS')->first();
        $agoProduct = Product::where('code', 'AGO')->first();
        $user = User::first();

        if (!$pmsProduct || !$agoProduct || !$user) {
            return;
        }

        $fuelTickets = [
            [
                'fuel_ticket_number' => '1227101234',
                'date' => Carbon::today()->subDays(5),
                'product_id' => $pmsProduct->id,
                'rate' => 650.00,
                'quantity' => 1000,
                'trip_allowance' => 20000,
                'total_amount' => 670000,
                'truck_capacity' => '33000 liters',
                'truck_number' => 'ABC-123-XY',
                'loading_point' => 'Depot Kano',
                'destination' => 'Maiduguri',
                'driver_name' => 'Musa Ibrahim',
                'driver_phone' => '08012345678',
                'truck_provider' => 'ABC Transport',
                'details' => 'Regular fuel delivery',
                'status' => 'Approved',
                'created_by' => $user->id,
            ],
            [
                'fuel_ticket_number' => '1227102345',
                'date' => Carbon::today()->subDays(3),
                'product_id' => $agoProduct->id,
                'rate' => 1200.00,
                'quantity' => 800,
                'trip_allowance' => 15000,
                'total_amount' => 975000,
                'truck_capacity' => '33000 liters',
                'truck_number' => 'XYZ-456-AB',
                'loading_point' => 'Depot Lagos',
                'destination' => 'Potiskum',
                'driver_name' => 'Ahmed Bello',
                'driver_phone' => '08098765432',
                'truck_provider' => 'XYZ Logistics',
                'details' => 'Diesel delivery for construction',
                'status' => 'Approved',
                'created_by' => $user->id,
            ],
            [
                'fuel_ticket_number' => '1228103456',
                'date' => Carbon::today()->subDays(1),
                'product_id' => $pmsProduct->id,
                'rate' => 650.00,
                'quantity' => 1200,
                'trip_allowance' => 18000,
                'total_amount' => 798000,
                'truck_capacity' => '45000 liters',
                'truck_number' => 'DEF-789-CD',
                'loading_point' => 'Depot Abuja',
                'destination' => 'Damaturu',
                'driver_name' => 'Yusuf Hassan',
                'driver_phone' => '08011223344',
                'truck_provider' => 'Sahara Transport',
                'details' => 'Emergency fuel supply',
                'status' => 'Pending',
                'created_by' => $user->id,
            ],
            [
                'fuel_ticket_number' => '1228104567',
                'date' => Carbon::today(),
                'product_id' => $agoProduct->id,
                'rate' => 1200.00,
                'quantity' => 950,
                'trip_allowance' => 20000,
                'total_amount' => 1160000,
                'truck_capacity' => '33000 liters',
                'truck_number' => 'GHI-012-EF',
                'loading_point' => 'Depot Kano',
                'destination' => 'Gashua',
                'driver_name' => 'Ibrahim Musa',
                'driver_phone' => '08055667788',
                'truck_provider' => 'Northern Haulage',
                'details' => 'Bulk diesel supply',
                'status' => 'Pending',
                'created_by' => $user->id,
            ],
        ];

        foreach ($fuelTickets as $ticket) {
            FuelTicket::create($ticket);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Main Station',
                'code' => 'MS001',
                'address' => 'No 3 Jama\'s Clinic Along Mohammed Idris',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'description' => 'Main filling station location',
                'status' => 'Active',
            ],
            [
                'name' => 'Head Office',
                'code' => 'HO001',
                'address' => 'No 15 Bank Road',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'description' => 'Administrative headquarters',
                'status' => 'Active',
            ],
            [
                'name' => 'Warehouse 1',
                'code' => 'WH001',
                'address' => 'Industrial Area',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'description' => 'Main storage warehouse',
                'status' => 'Active',
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}

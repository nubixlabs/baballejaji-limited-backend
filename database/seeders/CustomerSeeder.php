<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'company' => 'ABC Transport Ltd',
                'contact_person' => 'John Doe',
                'phone_number' => '08012345678',
                'email' => 'john@abctransport.com',
                'address' => 'No 45 Lagos Street',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'customer_type' => 'bulk',
                'credit_limit' => 500000.00,
                'credit_balance' => 0.00,
            ],
            [
                'company' => 'XYZ Logistics',
                'contact_person' => 'Jane Smith',
                'phone_number' => '08098765432',
                'email' => 'jane@xyzlogistics.com',
                'address' => 'No 12 Kano Road',
                'city' => 'Damaturu',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'customer_type' => 'bulk',
                'credit_limit' => 750000.00,
                'credit_balance' => 0.00,
            ],
            [
                'company' => 'Sahara Transport Services',
                'contact_person' => 'Ahmed Mohammed',
                'phone_number' => '08011223344',
                'email' => 'ahmed@saharatransport.com',
                'address' => 'No 8 Maiduguri Road',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'customer_type' => 'bulk',
                'credit_limit' => 1000000.00,
                'credit_balance' => 0.00,
            ],
            [
                'company' => 'Northern Haulage',
                'contact_person' => 'Ibrahim Yusuf',
                'phone_number' => '08055667788',
                'email' => 'ibrahim@northernhaulage.com',
                'address' => 'No 23 Fika Road',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'customer_type' => 'bulk',
                'credit_limit' => 600000.00,
                'credit_balance' => 0.00,
            ],
            [
                'company' => 'Express Trucking',
                'contact_person' => 'Fatima Hassan',
                'phone_number' => '08099887766',
                'email' => 'fatima@expresstrucking.com',
                'address' => 'No 34 Gashua Street',
                'city' => 'Potiskum',
                'state' => 'Yobe',
                'country' => 'Nigeria',
                'customer_type' => 'bulk',
                'credit_limit' => 800000.00,
                'credit_balance' => 0.00,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}

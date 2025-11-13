<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Nigerian National Petroleum Corporation (NNPC)',
                'contactPerson' => 'Musa Ibrahim',
                'email' => 'supply@nnpc.gov.ng',
                'phone' => '+234-803-123-4567',
                'address' => 'NNPC Towers, Central Business District',
                'city' => 'Abuja',
                'state' => 'FCT',
                'country' => 'Nigeria',
                'paymentTerms' => '30 days',
                'notes' => 'Primary fuel supplier - Government owned',
                'rating' => 5,
                'totalOrders' => 0,
                'totalValue' => 0.00,
                'lastOrderDate' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Total Energies Nigeria Limited',
                'contactPerson' => 'Jean-Pierre Dubois',
                'email' => 'nigeria@totalenergies.com',
                'phone' => '+234-701-234-5678',
                'address' => '4 Afribank Street, Victoria Island',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'paymentTerms' => '21 days',
                'notes' => 'International oil company with reliable supply chain',
                'rating' => 5,
                'totalOrders' => 0,
                'totalValue' => 0.00,
                'lastOrderDate' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Mobil Producing Nigeria Unlimited',
                'contactPerson' => 'Sarah Johnson',
                'email' => 'supply@exxonmobil.com',
                'phone' => '+234-802-345-6789',
                'address' => 'Lekki Peninsula, Eko Atlantic',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'paymentTerms' => '45 days',
                'notes' => 'Premium quality petroleum products',
                'rating' => 4,
                'totalOrders' => 0,
                'totalValue' => 0.00,
                'lastOrderDate' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Conoil Plc',
                'contactPerson' => 'Adebayo Ogundimu',
                'email' => 'procurement@conoil.com',
                'phone' => '+234-805-456-7890',
                'address' => '38 Warehouse Road, Apapa',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'paymentTerms' => '14 days',
                'notes' => 'Local supplier with competitive pricing',
                'rating' => 4,
                'totalOrders' => 0,
                'totalValue' => 0.00,
                'lastOrderDate' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Oando Plc',
                'contactPerson' => 'Kemi Adeosun',
                'email' => 'supply@oando.com',
                'phone' => '+234-807-567-8901',
                'address' => '2 Ajose Adeogun Street, Victoria Island',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'paymentTerms' => '30 days',
                'notes' => 'Integrated energy company with nationwide coverage',
                'rating' => 4,
                'totalOrders' => 0,
                'totalValue' => 0.00,
                'lastOrderDate' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Forte Oil Plc (Ardova)',
                'contactPerson' => 'Chinedu Okwu',
                'email' => 'procurement@ardovaplc.com',
                'phone' => '+234-809-678-9012',
                'address' => '29 Ademola Street, Ikoyi',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'paymentTerms' => '21 days',
                'notes' => 'Reliable supplier with good delivery schedules',
                'rating' => 3,
                'totalOrders' => 0,
                'totalValue' => 0.00,
                'lastOrderDate' => null,
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::updateOrCreate(
                ['email' => $supplierData['email']],
                $supplierData
            );
        }

        $this->command->info('Suppliers seeded successfully!');
    }
}
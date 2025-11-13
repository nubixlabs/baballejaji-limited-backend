<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds for testing purposes.
     * This seeder includes all the essential data needed for testing the application.
     */
    public function run(): void
    {
        $this->command->info('Seeding test data...');
        
        $this->call([
            ProductSeeder::class,
            SupplierSeeder::class,
            AccountSeeder::class,
            SettingSeeder::class,
        ]);
        
        $this->command->info('Test data seeded successfully!');
        $this->command->info('You now have:');
        $this->command->info('- 6 Products (PMS, AGO, DPK, LPG, Lubricants, Additives)');
        $this->command->info('- 6 Suppliers (NNPC, Total, Mobil, Conoil, Oando, Ardova)');
        $this->command->info('- Chart of Accounts');
        $this->command->info('- System Settings');
    }
}
<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Entry Level',
                'basic_pay_rate' => 50000,
                'basic_pay_period' => 'monthly',
                'overtime_rate' => 500,
                'overtime_period' => 'hourly',
                'description' => 'Entry level staff position',
            ],
            [
                'name' => 'Junior Level',
                'basic_pay_rate' => 80000,
                'basic_pay_period' => 'monthly',
                'overtime_rate' => 800,
                'overtime_period' => 'hourly',
                'description' => 'Junior level staff position',
            ],
            [
                'name' => 'Mid Level',
                'basic_pay_rate' => 120000,
                'basic_pay_period' => 'monthly',
                'overtime_rate' => 1200,
                'overtime_period' => 'hourly',
                'description' => 'Mid level staff position',
            ],
            [
                'name' => 'Senior Level',
                'basic_pay_rate' => 180000,
                'basic_pay_period' => 'monthly',
                'overtime_rate' => 1800,
                'overtime_period' => 'hourly',
                'description' => 'Senior level staff position',
            ],
            [
                'name' => 'Management Level',
                'basic_pay_rate' => 250000,
                'basic_pay_period' => 'monthly',
                'overtime_rate' => 2500,
                'overtime_period' => 'hourly',
                'description' => 'Management level position',
            ],
        ];

        foreach ($levels as $level) {
            Level::create($level);
        }
    }
}

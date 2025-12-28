<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Administration', 'description' => 'Administrative and management staff'],
            ['name' => 'Operations', 'description' => 'Filling station operations and retail'],
            ['name' => 'Transport', 'description' => 'Transport and logistics division'],
            ['name' => 'Accounts', 'description' => 'Finance and accounting department'],
            ['name' => 'Maintenance', 'description' => 'Equipment and facility maintenance'],
            ['name' => 'Security', 'description' => 'Security and safety personnel'],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}

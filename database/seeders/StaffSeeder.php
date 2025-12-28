<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Department;
use App\Models\Level;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $operations = Department::where('name', 'Operations')->first();
        $admin = Department::where('name', 'Administration')->first();
        $transport = Department::where('name', 'Transport')->first();
        
        $midLevel = Level::where('name', 'Mid Level')->first();
        $juniorLevel = Level::where('name', 'Junior Level')->first();
        $seniorLevel = Level::where('name', 'Senior Level')->first();

        if (!$operations || !$admin || !$transport || !$midLevel || !$juniorLevel || !$seniorLevel) {
            return;
        }

        $staff = [
            [
                'staff_id' => 'STF001',
                'name' => 'Abubakar Mohammed',
                'email' => 'abubakar@baballejaji.com',
                'phone' => '08012345001',
                'address' => 'No 10 Station Road, Potiskum',
                'department_id' => $operations->id,
                'level_id' => $seniorLevel->id,
                'hire_date' => '2020-01-15',
                'status' => 'active',
            ],
            [
                'staff_id' => 'STF002',
                'name' => 'Hauwa Ibrahim',
                'email' => 'hauwa@baballejaji.com',
                'phone' => '08012345002',
                'address' => 'No 25 Market Street, Potiskum',
                'department_id' => $admin->id,
                'level_id' => $midLevel->id,
                'hire_date' => '2021-03-20',
                'status' => 'active',
            ],
            [
                'staff_id' => 'STF003',
                'name' => 'Yusuf Garba',
                'email' => 'yusuf@baballejaji.com',
                'phone' => '08012345003',
                'address' => 'No 5 Fika Road, Potiskum',
                'department_id' => $operations->id,
                'level_id' => $juniorLevel->id,
                'hire_date' => '2022-06-10',
                'status' => 'active',
            ],
            [
                'staff_id' => 'STF004',
                'name' => 'Fatima Usman',
                'email' => 'fatima@baballejaji.com',
                'phone' => '08012345004',
                'address' => 'No 18 Gashua Street, Potiskum',
                'department_id' => $admin->id,
                'level_id' => $juniorLevel->id,
                'hire_date' => '2022-09-01',
                'status' => 'active',
            ],
            [
                'staff_id' => 'STF005',
                'name' => 'Musa Aliyu',
                'email' => 'musa@baballejaji.com',
                'phone' => '08012345005',
                'address' => 'No 42 Kano Road, Potiskum',
                'department_id' => $transport->id,
                'level_id' => $midLevel->id,
                'hire_date' => '2021-11-15',
                'status' => 'active',
            ],
        ];

        foreach ($staff as $member) {
            Staff::create($member);
        }
    }
}

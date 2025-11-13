<?php

namespace Database\Seeders;

use App\Models\UserGroup;
use Illuminate\Database\Seeder;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $userGroups = [
            [
                'name' => 'Administrators',
                'description' => 'Full system access with all permissions',
                'permissions' => [
                    'Add products', 'View products', 'Edit products', 'Delete product',
                    'Add purchase', 'View purchases', 'Edit purchase', 'Delete purchase',
                    'Add sale', 'View sales', 'Edit sales', 'Delete sales',
                    'View users', 'Create users', 'Edit users', 'Delete users',
                    'View usergroups', 'Create usergroups', 'Edit usergroups', 'Delete usergroups',
                    'View Activity Logs', 'View Login Logs', 'Update settings'
                ],
            ],
            [
                'name' => 'Service Station Managers',
                'description' => 'Managers with operational permissions',
                'permissions' => [
                    'Add products', 'View products', 'Edit products', 'Update prices',
                    'View Inventory', 'View Shortages', 'Edit Inventory value',
                    'Add sale', 'View sales', 'Edit sales', 'Approve sales',
                    'Add Tank', 'View tanks', 'Edit tank', 'Manage nozzles',
                    'View dispensations', 'Add dispensation', 'Approve dispensation',
                    'Open shift', 'Close shift', 'View shifts', 'Approve shift'
                ],
            ],
            [
                'name' => 'Cashiers',
                'description' => 'Basic sales and customer service permissions',
                'permissions' => [
                    'View products', 'Add sale', 'View Own Sales', 'Reprint Receipt',
                    'Add customer', 'View customers', 'Edit customer',
                    'Open shift', 'Close shift', 'View shifts'
                ],
            ],
            [
                'name' => 'Attendants',
                'description' => 'Fuel dispensing and basic operations',
                'permissions' => [
                    'View products', 'Add dispensation', 'View dispensations',
                    'View tanks', 'View dippings', 'Add/Edit dipping',
                    'View shifts', 'Attendants'
                ],
            ]
        ];

        foreach ($userGroups as $group) {
            UserGroup::create($group);
        }
    }
}
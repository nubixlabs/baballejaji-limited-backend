<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking User Groups:\n";
$groups = App\Models\UserGroup::all();
if ($groups->isEmpty()) {
    echo "No user groups found. Creating default groups...\n";
    
    $defaultGroups = [
        ['name' => 'Filling Station', 'description' => 'Users for filling station management'],
        ['name' => 'Head Office', 'description' => 'Head office administrative users'],
        ['name' => 'Spare Parts', 'description' => 'Users for spare parts management'],
        ['name' => 'Transport', 'description' => 'Users for transport management'],
    ];
    
    foreach ($defaultGroups as $group) {
        App\Models\UserGroup::firstOrCreate(['name' => $group['name']], $group);
        echo "Created: " . $group['name'] . "\n";
    }
    
    echo "\nAll User Groups:\n";
    App\Models\UserGroup::all()->each(function($g) {
        echo "ID: " . $g->id . " - Name: " . $g->name . "\n";
    });
} else {
    echo "Found " . $groups->count() . " user groups:\n";
    $groups->each(function($g) {
        echo "ID: " . $g->id . " - Name: " . $g->name . "\n";
    });
}

echo "\n\nChecking Roles:\n";
$roles = App\Models\Role::all();
if ($roles->isEmpty()) {
    echo "No roles found. Creating default roles...\n";
    
    $defaultRoles = [
        ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'System administrator'],
        ['name' => 'filling_station', 'display_name' => 'Filling Station', 'description' => 'Filling station users'],
        ['name' => 'transport', 'display_name' => 'Transport', 'description' => 'Transport users'],
        ['name' => 'spare_parts', 'display_name' => 'Spare Parts', 'description' => 'Spare parts users'],
    ];
    
    foreach ($defaultRoles as $role) {
        App\Models\Role::firstOrCreate(['name' => $role['name']], $role);
        echo "Created: " . $role['display_name'] . "\n";
    }
    
    echo "\nAll Roles:\n";
    App\Models\Role::all()->each(function($r) {
        echo "ID: " . $r->id . " - Name: " . $r->name . " - Display: " . $r->display_name . "\n";
    });
} else {
    echo "Found " . $roles->count() . " roles:\n";
    $roles->each(function($r) {
        echo "ID: " . $r->id . " - Name: " . $r->name . " - Display: " . $r->display_name . "\n";
    });
}

echo "\nDone!\n";
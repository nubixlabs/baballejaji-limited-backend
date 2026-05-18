<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$role = App\Models\Role::where('name', 'super_admin')->first();
if (!$role) {
    echo "Creating super_admin role...\n";
    $role = App\Models\Role::create([
        'name' => 'super_admin',
        'display_name' => 'Super Admin',
        'description' => 'System administrator with full access'
    ]);
    echo "Role created: " . $role->name . " (ID: " . $role->id . ")\n";
} else {
    echo "Role found: " . $role->name . " (ID: " . $role->id . ")\n";
}

$user = App\Models\User::updateOrCreate(
    ['email' => 'admin@gmail.com'],
    [
        'name' => 'Super Admin',
        'password' => bcrypt('123456789'),
        'role_id' => $role->id,
        'is_active' => true
    ]
);

echo "User created/updated: " . $user->email . "\n";
echo "Role: " . $user->role->name . "\n";
echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
echo "\nLogin credentials:\n";
echo "Email: admin@gmail.com\n";
echo "Password: 123456789\n";
<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing User API...\n";

try {
    $users = App\Models\User::with(['role', 'userGroup', 'fillingStation'])->get();
    echo "Found " . $users->count() . " users\n\n";
    
    $users->take(5)->each(function($u) {
        echo "User ID: " . $u->id . "\n";
        echo "  Name: " . $u->name . "\n";
        echo "  Email: " . $u->email . "\n";
        echo "  Role: " . ($u->role ? $u->role->name : 'None') . "\n";
        echo "  User Group: " . ($u->userGroup ? $u->userGroup->name : 'None') . "\n";
        echo "  Filling Station: " . ($u->fillingStation ? $u->fillingStation->name : 'None') . "\n";
        echo "  Active: " . ($u->is_active ? 'Yes' : 'No') . "\n";
        echo "\n";
    });
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
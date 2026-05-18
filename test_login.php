<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing login...\n";

try {
    $user = App\Models\User::where('email', 'admin@gmail.com')->first();
    if ($user) {
        echo "Admin user found: " . $user->name . "\n";
        echo "Password hash exists: " . (strlen($user->password) > 0 ? 'Yes' : 'No') . "\n";
    } else {
        echo "Admin user NOT found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Sanctum...\n";

try {
    $tables = ['personal_access_tokens'];
    foreach($tables as $table) {
        try {
            $exists = DB::getSchemaBuilder()->hasTable($table);
            echo "  $table: " . ($exists ? 'exists' : 'NOT found') . "\n";
        } catch(Exception $e) {
            echo "  $table: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nChecking User model...\n";
    $user = App\Models\User::first();
    if ($user) {
        echo "User found: " . $user->name . "\n";
        echo "Has createToken method: " . (method_exists($user, 'createToken') ? 'Yes' : 'No') . "\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
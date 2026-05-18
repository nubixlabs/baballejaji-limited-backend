<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shift;

$shifts = Shift::whereIn('status', ['closed', 'approved'])->get();
$controller = app(App\Http\Controllers\ShiftController::class);
$reflection = new ReflectionMethod($controller, 'calculateShiftSales');
$reflection->setAccessible(true);
$count = 0;

foreach ($shifts as $shift) {
    $reflection->invoke($controller, $shift);
    $shift->save();
    $count++;
}

echo "Recalculated sales for {$count} shifts\n";

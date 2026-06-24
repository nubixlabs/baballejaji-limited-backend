<?php
/**
 * Backfill Script: Assign filling_station_id to existing records
 * 
 * Run after migration: php artisan migrate
 * Then run: php backfill_filling_station_id.php
 * 
 * This assigns all NULL filling_station_id records to the first available filling station.
 * If records have a created_by user with a default filling station, it uses that instead.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FillingStation;

// Get the first filling station as default
$defaultStation = FillingStation::first();
if (!$defaultStation) {
    echo "ERROR: No filling station found. Please create at least one filling station first.\n";
    exit(1);
}
$defaultStationId = $defaultStation->id;
echo "Using default filling station: {$defaultStation->name} (ID: {$defaultStationId})\n\n";

// Tables that need backfilling (have filling_station_id column)
$tables = [
    'products', 'customers', 'suppliers', 'tank_groups', 'tank_transfers',
    'distributions', 'fuel_tickets', 'customer_payments', 'payments', 'bank_transfers',
    'asset_categories', 'assets', 'locations', 'accounts', 'vouchers',
    'journal_entries', 'departments', 'levels', 'loans', 'attendance_records',
    'vacations', 'payslips', 'salary_payments', 'stock_levels', 'shift_sales_summaries',
    'parts', 'orders', 'order_items', 'bulk_sale_items', 'retail_sale_items',
    'purchase_items', 'voucher_line_items', 'journal_entry_lines', 'account_balances',
    'holidays', 'activity_logs', 'login_logs', 'settings',
    // Tables that already have the column but might have NULLs
    'shifts', 'tanks', 'daily_sales', 'bulk_sales', 'retail_sales',
    'purchases', 'staff', 'price_adjustments', 'tank_dippings',
    'inventory_reconciliations', 'nozzles',
];

$totalUpdated = 0;

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "  SKIP: {$table} (table does not exist)\n";
        continue;
    }

    if (!Schema::hasColumn($table, 'filling_station_id')) {
        echo "  SKIP: {$table} (no filling_station_id column)\n";
        continue;
    }

    $count = DB::table($table)->whereNull('filling_station_id')->count();
    if ($count === 0) {
        echo "  OK:   {$table} (0 records need backfill)\n";
        continue;
    }

    // Try to assign by creator's default station first
    if (Schema::hasColumn($table, 'created_by')) {
        $updated = DB::table($table)
            ->whereNull('filling_station_id')
            ->whereNotNull('created_by')
            ->update([
                'filling_station_id' => DB::raw(
                    "(SELECT COALESCE(filling_station_id, {$defaultStationId}) FROM users WHERE id = {$table}.created_by LIMIT 1)"
                )
            ]);
        echo "  UPD:  {$table} (assigned {$updated} by creator's station)\n";
        $totalUpdated += $updated;
    }

    // Assign remaining NULLs to default station
    $remaining = DB::table($table)->whereNull('filling_station_id')->count();
    if ($remaining > 0) {
        DB::table($table)->whereNull('filling_station_id')->update([
            'filling_station_id' => $defaultStationId
        ]);
        echo "  UPD:  {$table} (assigned {$remaining} to default station)\n";
        $totalUpdated += $remaining;
    }
}

echo "\nDone! Total records updated: {$totalUpdated}\n";

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'products', 'customers', 'suppliers', 'tank_groups', 'tank_transfers',
        'distributions', 'fuel_tickets', 'customer_payments', 'payments', 'bank_transfers',
        'asset_categories', 'assets', 'locations', 'accounts', 'vouchers',
        'journal_entries', 'departments', 'levels', 'loans', 'attendance_records',
        'vacations', 'payslips', 'salary_payments', 'stock_levels', 'shift_sales_summaries',
        'parts', 'orders', 'order_items', 'bulk_sale_items', 'retail_sale_items',
        'purchase_items', 'voucher_line_items', 'journal_entry_lines', 'account_balances',
        'holidays', 'activity_logs', 'login_logs', 'settings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'filling_station_id')) {
                    $table->foreignId('filling_station_id')->nullable()->after('id')->constrained('filling_stations')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'filling_station_id')) {
                    $table->dropForeign(['filling_station_id']);
                    $table->dropColumn('filling_station_id');
                }
            });
        }
    }
};

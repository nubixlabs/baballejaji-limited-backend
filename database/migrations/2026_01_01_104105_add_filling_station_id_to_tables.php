<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'shifts',
            'tanks',
            'daily_sales',
            'bulk_sales',
            'retail_sales',
            'purchases',
            'staff',
            'price_adjustments',
            'tank_dippings',
            'expenses',
            'inventory_reconciliations'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'filling_station_id')) {
                        $table->foreignId('filling_station_id')->nullable()->after('id')->constrained('filling_stations')->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'shifts',
            'tanks',
            'daily_sales',
            'bulk_sales',
            'retail_sales',
            'purchases',
            'staff',
            'price_adjustments',
            'tank_dippings',
            'expenses',
            'inventory_reconciliations'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'filling_station_id')) {
                        $table->dropForeign(['filling_station_id']);
                        $table->dropColumn('filling_station_id');
                    }
                });
            }
        }
    }
};

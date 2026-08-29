<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filling_station_product', function (Blueprint $table) {
            $table->decimal('cost_price', 14, 2)->nullable()->after('product_id');
            $table->decimal('retail_price', 14, 2)->nullable()->after('cost_price');
            $table->decimal('dealer_price', 14, 2)->nullable()->after('retail_price');
            $table->decimal('bulk_price', 14, 2)->nullable()->after('dealer_price');
        });

        // Migrate existing product prices into pivot rows that already have data
        $driver = DB::connection()->getDriverName();
        $now = $driver === 'sqlite' ? "datetime('now')" : 'NOW()';
        DB::statement("
            UPDATE filling_station_product
            SET cost_price = (SELECT cost_price FROM products WHERE products.id = filling_station_product.product_id),
                retail_price = (SELECT retail_price FROM products WHERE products.id = filling_station_product.product_id),
                dealer_price = (SELECT dealer_price FROM products WHERE products.id = filling_station_product.product_id),
                bulk_price = (SELECT bulk_price FROM products WHERE products.id = filling_station_product.product_id)
        ");
    }

    public function down(): void
    {
        Schema::table('filling_station_product', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'retail_price', 'dealer_price', 'bulk_price']);
        });
    }
};

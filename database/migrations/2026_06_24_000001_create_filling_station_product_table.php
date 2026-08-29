<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('filling_station_product')) {
            Schema::create('filling_station_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('filling_station_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['filling_station_id', 'product_id']);
            });
        }

        $count = DB::table('filling_station_product')->count();
        if ($count === 0) {
            $driver = DB::connection()->getDriverName();
            $now = $driver === 'sqlite' ? "datetime('now')" : 'NOW()';
            DB::statement("INSERT INTO filling_station_product (filling_station_id, product_id, created_at, updated_at) SELECT filling_station_id, id, {$now}, {$now} FROM products WHERE filling_station_id IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('filling_station_product');
    }
};

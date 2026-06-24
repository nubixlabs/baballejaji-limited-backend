<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filling_station_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filling_station_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['filling_station_id', 'product_id']);
        });

        DB::statement('INSERT INTO filling_station_product (filling_station_id, product_id, created_at, updated_at) SELECT filling_station_id, id, NOW(), NOW() FROM products WHERE filling_station_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('filling_station_product');
    }
};

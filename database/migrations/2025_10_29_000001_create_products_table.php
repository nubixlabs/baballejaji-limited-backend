<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PMS, AGO, etc.
            $table->string('name'); // Petrol, Diesel, etc.
            $table->string('si_unit')->default('Litres'); // Standard unit of measurement
            $table->decimal('quantity', 15, 2)->default(0); // Current stock quantity
            $table->decimal('cost_price', 15, 2)->default(0); // Purchase price
            $table->decimal('retail_price', 15, 2)->default(0); // Retail selling price
            $table->decimal('dealer_price', 15, 2)->default(0); // Dealer price
            $table->decimal('bulk_price', 15, 2)->default(0); // Bulk selling price
            $table->decimal('re_order_level', 15, 2)->default(0); // Re-order threshold
            $table->string('iot_product')->nullable(); // IoT product reference
            $table->integer('created_by')->nullable();
            $table->integer('last_modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


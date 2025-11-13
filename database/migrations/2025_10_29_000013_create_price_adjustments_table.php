<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('old_cost_price', 15, 2)->nullable();
            $table->decimal('new_cost_price', 15, 2)->nullable();
            $table->decimal('old_retail_price', 15, 2)->nullable();
            $table->decimal('new_retail_price', 15, 2)->nullable();
            $table->decimal('old_dealer_price', 15, 2)->nullable();
            $table->decimal('new_dealer_price', 15, 2)->nullable();
            $table->decimal('old_bulk_price', 15, 2)->nullable();
            $table->decimal('new_bulk_price', 15, 2)->nullable();
            $table->date('adjustment_date');
            $table->text('reason')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_adjustments');
    }
};


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
        Schema::create('shift_sales_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('pump_price', 12, 2)->default(0);
            $table->decimal('shift_vol', 12, 2)->default(0);
            $table->decimal('shift_amount', 12, 2)->default(0);
            $table->decimal('bulk_sales', 12, 2)->default(0);
            $table->decimal('retail_sales', 12, 2)->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_sales_summaries');
    }
};

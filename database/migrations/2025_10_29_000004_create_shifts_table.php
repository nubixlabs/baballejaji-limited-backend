<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_id')->unique();
            $table->string('name');
            $table->date('date');
            $table->decimal('cash_sales', 15, 2)->default(0);
            $table->decimal('credit_sales', 15, 2)->default(0);
            $table->decimal('sales_revenue', 15, 2)->default(0);
            $table->string('status')->default('open'); // open, closed, approved
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('closed_by')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};


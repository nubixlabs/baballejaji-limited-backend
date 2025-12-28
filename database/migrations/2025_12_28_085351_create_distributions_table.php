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
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_sale_id')->constrained('bulk_sales')->onDelete('cascade');
            $table->foreignId('tank_id')->constrained('tanks');
            $table->foreignId('nozzle_id')->constrained('nozzles');
            $table->decimal('quantity', 15, 2);
            $table->string('destination')->nullable();
            $table->date('sale_date');
            $table->string('waybill_no')->nullable();
            $table->string('narration')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};

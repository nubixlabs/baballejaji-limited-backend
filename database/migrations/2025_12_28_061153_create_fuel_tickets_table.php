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
        Schema::create('fuel_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('fuel_ticket_number')->unique();
            $table->date('date');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('rate', 10, 2);
            $table->decimal('quantity', 10, 2);
            $table->decimal('trip_allowance', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('truck_capacity')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('loading_point')->nullable();
            $table->string('destination')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('truck_provider')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('details')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_tickets');
    }
};

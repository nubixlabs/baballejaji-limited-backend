<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_tank_id')->constrained('tanks')->onDelete('cascade');
            $table->foreignId('to_tank_id')->constrained('tanks')->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_transfers');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tank_dippings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained()->onDelete('cascade');
            $table->decimal('dipped_quantity', 15, 2);
            $table->decimal('atg_quantity', 15, 2)->nullable();
            $table->decimal('variance', 15, 2)->default(0);
            $table->date('dipping_date');
            $table->text('notes')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_dippings');
    }
};


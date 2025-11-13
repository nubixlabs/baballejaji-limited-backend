<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('capacity', 15, 2); // Tank capacity in litres
            $table->decimal('content', 15, 2)->default(0); // Current content in litres
            $table->decimal('level', 5, 2)->default(0); // Fill level percentage
            $table->string('atg_status')->default('Offline'); // ATG (Automatic Tank Gauge) status
            $table->string('group')->default('PRODUCT TANKS'); // Tank group
            $table->string('fillup_id')->nullable(); // FillUp integration ID
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};


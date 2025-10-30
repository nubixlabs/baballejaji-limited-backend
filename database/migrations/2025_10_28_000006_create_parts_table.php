<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('partNumber')->unique();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('vehicleType')->nullable();
            $table->decimal('costPrice', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('stock')->default(0);
            $table->decimal('minStock')->default(0);
            $table->decimal('maxStock')->default(0);
            $table->text('description')->nullable();

            // Relation to suppliers table
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};

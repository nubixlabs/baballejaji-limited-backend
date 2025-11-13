<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('asset_tag')->nullable()->unique();
            $table->string('serial_number')->nullable();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable(); // Percentage
            $table->string('condition')->default('Good'); // Excellent, Good, Fair, Poor
            $table->string('status')->default('Active'); // Active, Inactive, Disposed, Under Maintenance
            $table->date('warranty_expiry')->nullable();
            $table->string('supplier')->nullable();
            $table->text('description')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('last_modified_by')->nullable();
            $table->timestamp('modified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
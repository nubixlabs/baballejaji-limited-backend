<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nozzles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('tank_id')->constrained('tanks')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->decimal('reading', 15, 2)->default(0); // Current reading
            $table->string('type')->nullable(); // Pump type
            $table->string('dispenser_type')->nullable();
            $table->boolean('is_online')->default(false);
            $table->integer('created_by')->nullable();
            $table->integer('last_modified_by')->nullable();
            $table->timestamp('modified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nozzles');
    }
};

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
        Schema::table('shifts', function (Blueprint $table) {
            // Drop the existing unique index on shift_id
            $table->dropUnique(['shift_id']);
            
            // Add a composite unique index on shift_id and filling_station_id
            $table->unique(['shift_id', 'filling_station_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['shift_id', 'filling_station_id']);
            $table->unique('shift_id');
        });
    }
};

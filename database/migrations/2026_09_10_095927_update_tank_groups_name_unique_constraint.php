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
        Schema::table('tank_groups', function (Blueprint $table) {
            // Drop the old unique constraint on name
            $table->dropUnique('tank_groups_name_unique');
            // Add a new unique constraint on name and filling_station_id
            $table->unique(['name', 'filling_station_id'], 'tank_groups_name_station_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tank_groups', function (Blueprint $table) {
            $table->dropUnique('tank_groups_name_station_unique');
            $table->unique('name', 'tank_groups_name_unique');
        });
    }
};

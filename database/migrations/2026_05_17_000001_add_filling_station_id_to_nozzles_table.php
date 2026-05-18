<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nozzles', function (Blueprint $table) {
            if (!Schema::hasColumn('nozzles', 'filling_station_id')) {
                $table->foreignId('filling_station_id')->nullable()->after('id')->constrained('filling_stations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nozzles', function (Blueprint $table) {
            if (Schema::hasColumn('nozzles', 'filling_station_id')) {
                $table->dropForeign(['filling_station_id']);
                $table->dropColumn('filling_station_id');
            }
        });
    }
};

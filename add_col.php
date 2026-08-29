<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('daily_sales', function (Blueprint $table) {
    if (!Schema::hasColumn('daily_sales', 'filling_station_id')) {
        $table->foreignId('filling_station_id')->nullable()->after('id')->constrained('filling_stations')->nullOnDelete();
    }
});
echo "Done\n";

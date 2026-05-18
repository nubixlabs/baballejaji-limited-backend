<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('nozzle_reading_name')->nullable()->after('expenses_data');
            $table->json('additional_readings')->nullable()->after('nozzle_reading_name');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['nozzle_reading_name', 'additional_readings']);
        });
    }
};

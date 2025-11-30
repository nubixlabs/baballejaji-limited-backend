<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('waybill_number');
            $table->string('driver_phone')->nullable()->after('driver_name');
            $table->foreignId('tank_id')->nullable()->after('driver_phone')->constrained('tanks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tank_id');
            $table->dropColumn(['driver_name', 'driver_phone']);
        });
    }
};

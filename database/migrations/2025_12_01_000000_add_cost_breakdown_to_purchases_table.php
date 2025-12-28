<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->json('cost_breakdown')->nullable()->after('notes');
            $table->string('truck_number')->nullable()->after('cost_breakdown');
            $table->string('waybill_number')->nullable()->after('truck_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['cost_breakdown', 'truck_number', 'waybill_number']);
        });
    }
};



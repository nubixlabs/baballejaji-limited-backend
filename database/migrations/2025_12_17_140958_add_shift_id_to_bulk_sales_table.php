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
        Schema::table('bulk_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('customer_id');
            // Assuming shifts table exists, but let's just make it a column for now or foreign key if needed.
            // Safe to make it nullable unsigned big integer.
            // $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null'); // Optional
        });
    }

    public function down(): void
    {
        Schema::table('bulk_sales', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
    }
};

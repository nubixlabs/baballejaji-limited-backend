<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            // Add last_modified_by to track who updated the payment
            $table->foreignId('last_modified_by')->nullable()->after('approved_by')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropForeign(['last_modified_by']);
            $table->dropColumn('last_modified_by');
        });
    }
};

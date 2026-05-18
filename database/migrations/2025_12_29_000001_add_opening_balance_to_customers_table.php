<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('opening_balance_debit', 15, 2)->default(0)->after('credit_balance');
            $table->decimal('opening_balance_credit', 15, 2)->default(0)->after('opening_balance_debit');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['opening_balance_debit', 'opening_balance_credit']);
        });
    }
};

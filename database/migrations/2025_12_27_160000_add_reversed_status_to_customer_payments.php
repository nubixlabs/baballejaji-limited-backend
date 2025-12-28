<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update comment to reflect new status options: pending, approved, reversed
        DB::statement("ALTER TABLE customer_payments MODIFY status VARCHAR(255) DEFAULT 'pending' COMMENT 'pending, approved, reversed'");
    }

    public function down(): void
    {
        // Revert to original comment
        DB::statement("ALTER TABLE customer_payments MODIFY status VARCHAR(255) DEFAULT 'pending' COMMENT 'pending, approved'");
    }
};

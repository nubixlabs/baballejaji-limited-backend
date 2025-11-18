<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('currently_employed_text')->default('Yes');
        });

        // Map existing boolean values to Yes/No
        DB::table('staff')->where('currently_employed', true)->update(['currently_employed_text' => 'Yes']);
        DB::table('staff')->where('currently_employed', false)->update(['currently_employed_text' => 'No']);

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('currently_employed');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->renameColumn('currently_employed_text', 'currently_employed');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->boolean('currently_employed_bool')->default(true);
        });

        // Convert Yes/No back to boolean true/false
        DB::table('staff')->where('currently_employed', 'Yes')->update(['currently_employed_bool' => true]);
        DB::table('staff')->where('currently_employed', 'No')->update(['currently_employed_bool' => false]);

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('currently_employed');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->renameColumn('currently_employed_bool', 'currently_employed');
        });
    }
};

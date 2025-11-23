<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('staff')->cascadeOnDelete();
            $table->string('emp_id');
            $table->decimal('total_pay', 12, 2);
            $table->string('cheque_account')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'paid_at']);
            $table->index(['payslip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};

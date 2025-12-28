<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('bulk_sale_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method'); // cash, bank_transfer, cheque
            $table->string('paid_by')->nullable();
            $table->string('received_by')->nullable();
            $table->text('details')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('pending'); // pending, approved
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};

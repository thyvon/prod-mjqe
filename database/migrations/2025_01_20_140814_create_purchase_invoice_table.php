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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('pi_number')->unique();
            $table->integer('transaction_type');
            $table->foreignId('cash_ref')->nullable()->constrained('cash_requests')->onDelete('restrict');
            $table->integer('payment_type')->default(1);
            $table->date('invoice_date');
            $table->string('invoice_no');
            $table->foreignId('supplier')->constrained('suppliers');
            $table->integer('currency')->default(1);
            $table->decimal('currency_rate', 8, 2);
            $table->integer('payment_term');
            $table->decimal('sub_total', 15, 8)->default(0);
            $table->decimal('vat_rate', 8, 2)->default(0);
            $table->decimal('vat_amount', 15, 8)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('service_charge', 15, 4)->default(0)->nullable(); // Add service_charge field
            $table->decimal('total_amount', 15, 8);
            $table->decimal('paid_amount', 15, 8)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};

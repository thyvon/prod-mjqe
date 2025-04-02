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
            $table->tinyInteger('transaction_type')->comment('1 = Petty Cash, 2 = Credit, 3 = Advance');
            $table->foreignId('cash_ref')->nullable()->constrained('cash_requests')->onDelete('restrict');
            $table->tinyInteger('payment_type')->default(1)->comment('1 = Final, 2 = Deposit');
            $table->date('invoice_date');
            $table->string('invoice_no');
            $table->foreignId('supplier')->constrained('suppliers');
            $table->tinyInteger('currency')->default(1)->comment('1 = USD, 2 = KHR');
            $table->decimal('currency_rate', 8, 2);
            $table->tinyInteger('payment_term')->comment('1 = Credit 1 week, 2 = Credit 2 weeks, 3 = Credit 1 month, 4 = Non-Credit');
            $table->decimal('sub_total', 15, 8)->default(0);
            $table->decimal('vat_rate', 8, 2)->default(0);
            $table->decimal('vat_amount', 15, 8)->default(0);
            $table->decimal('discount_total', 15, 4)->default(0);
            $table->decimal('service_charge', 15, 4)->default(0)->nullable();
            $table->decimal('total_amount', 15, 8);
            $table->decimal('paid_amount', 15, 8)->default(0);
            $table->decimal('paid_usd', 23, 15)->default(0);
            // $table->foreignId('purchased_by')->constrained('users');
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

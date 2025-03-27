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
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pi_number')->constrained('purchase_invoices')->onDelete('cascade');
            $table->date('invoice_date');
            $table->integer('payment_type');
            $table->string('invoice_no');
            $table->foreignId('pr_number')->constrained('purchase_requests');
            $table->foreignId('po_number')->nullable()->constrained('purchase_orders');
            $table->foreignId('pr_item')->constrained('pr_items');
            $table->foreignId('po_item')->nullable()->constrained('po_items');
            $table->foreignId('supplier')->constrained('suppliers');
            $table->foreignId('item_code')->constrained('products');
            $table->string('description');
            $table->string('remark')->nullable();
            $table->decimal('qty', 15, 2);
            $table->string('uom');
            $table->integer('currency')->default(1);
            $table->decimal('currency_rate', 8, 2);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_price', 15, 8);
            $table->decimal('discount', 23, 15)->nullable()->default(0);
            $table->decimal('vat', 15, 4)->nullable()->default(0);
            $table->decimal('return', 15, 4)->nullable()->default(0);
            $table->decimal('retention', 15, 4)->nullable()->default(0);
            $table->decimal('service_charge', 23, 15)->nullable()->default(0);
            $table->decimal('deposit', 15, 4)->nullable()->default(0);
            $table->decimal('total_usd', 23, 15)->default(0);
            $table->decimal('total_khr', 23, 15)->default(0);
            $table->decimal('paid_amount', 15, 8)->default(0);
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->string('campus');
            $table->string('division');
            $table->string('department');
            $table->string('location');
            $table->foreignId('purchased_by')->constrained('users');
            $table->string('purpose');
            $table->integer('payment_term');
            $table->integer('transaction_type');
            $table->foreignId('cash_ref')->nullable()->constrained('cash_requests'); // Corrected table name
            $table->boolean('stop_purchase')->default(false);
            $table->integer('asset_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};

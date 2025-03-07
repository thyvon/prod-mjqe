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
        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders');
            $table->foreignId('pr_id')->constrained('purchase_requests');
            $table->foreignId('pr_item_id')->constrained('pr_items');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('campus');
            $table->string('division');
            $table->string('department');
            $table->string('location');
            $table->text('description')->nullable();
            $table->decimal('qty', 10, 4);
            $table->string('uom');
            $table->string('currency', 10);
            $table->decimal('currency_rate', 10, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('vat', 15, 4)->nullable();
            $table->decimal('total_usd', 15, 4);
            $table->decimal('total_khr', 15, 4);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('due_amount', 15, 4)->default(0);
            $table->decimal('received_qty', 10, 4)->default(0);
            $table->decimal('cancelled_qty', 10, 4)->default(0);
            $table->decimal('pending', 10, 4)->default(0);
            $table->foreignId('purchaser_id')->constrained('users');
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancelled_reason')->nullable();
            $table->string('status', 50)->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_items');
    }
};

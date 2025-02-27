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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date'); // Date field
            $table->integer('currency')->default(1);
            $table->decimal('currency_rate', 10, 2);
            $table->string('po_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('purpose')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->decimal('vat', 10, 2)->nullable();
            $table->integer('total_item')->default(0);
            $table->integer('cancelled_item')->default(0);
            $table->integer('purchased_item')->default(0);
            $table->integer('pending_item')->default(0);
            $table->string('payment_term', 255)->nullable();
            $table->decimal('total_amount_usd', 15, 2)->nullable();
            $table->decimal('total_amount_khr', 15, 2)->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
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
        Schema::dropIfExists('purchase_orders');
    }
};

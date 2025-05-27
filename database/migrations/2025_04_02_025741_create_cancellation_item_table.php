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
        Schema::create('cancellation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_id')->constrained('cancellations')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('po_items');
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests');
            $table->foreignId('purchase_request_item_id')->nullable()->constrained('pr_items');
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancellation_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellation_items');
    }
};

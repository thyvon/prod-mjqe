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
        Schema::create('pr_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade'); // Foreign key to purchase_requests table
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Foreign key to products table
            $table->string('remark')->nullable();
            $table->decimal('qty', 10, 2);
            $table->string('uom');
            $table->decimal('price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('campus');
            $table->string('division');
            $table->string('department');
            $table->decimal('qty_cancel', 10, 2)->default(0);
            $table->decimal('qty_last', 10, 2)->default(0);
            $table->decimal('qty_po', 10, 2)->default(0);
            $table->decimal('qty_purchase', 10, 2)->default(0);
            $table->string('status')->default('Pending');
            $table->boolean('force_close')->default(false);
            $table->boolean('is_cancel')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_items');
    }
};

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('product_description');
            $table->string('brand');
            $table->string('uom');
            $table->foreignId('category_id')->constrained('product_categories');
            $table->foreignId('group_id')->constrained('product_groups');
            $table->decimal('price', 10, 2);
            $table->decimal('quantity', 10, 2);
            $table->tinyInteger('status')->default(1);  // 1 = active, 0 = inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

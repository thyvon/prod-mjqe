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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kh_name')->nullable();
            $table->string('number');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('payment_term')->nullable();
            $table->decimal('vat', 5, 2)->nullable(); // Add vat field
            $table->tinyInteger('status')->default(1);  // 1 = active, 0 = inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

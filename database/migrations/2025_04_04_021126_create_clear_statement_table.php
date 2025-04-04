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
        Schema::create('clear_statements', function (Blueprint $table) {
            $table->id();
            $table->string('statement_number')->unique(); // Add this column
            $table->foreignId('clear_by_id')->constrained('users');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('clear_date');
            $table->decimal('total_amount', 23, 15)->default(0);
            $table->integer('total_invoices')->default(0);
            $table->string('description')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clear_statements');
    }
};

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
        Schema::create('clear_statement_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clear_statement_id')->constrained('clear_statements');
            $table->foreignId('invoice_id')->unique()->constrained('purchase_invoices');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('clear_by_id')->constrained('users');
            $table->date('clear_date');
            $table->decimal('total_amount', 23, 15)->default(0);
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clear_statement_invoices');
    }
};

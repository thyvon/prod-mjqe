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
        Schema::create('clear_invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('clear_type');
            $table->string('ref_no')->unique(); // Unique reference number
            $table->foreignId('cash_id')->constrained('cash_requests')->onDelete('cascade');
            $table->date('clear_date');
            $table->foreignId('clear_by')->constrained('users');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clear_invoices');
    }
};

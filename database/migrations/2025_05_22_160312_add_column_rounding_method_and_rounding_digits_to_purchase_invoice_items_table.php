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
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->string('rounding_method')->nullable()->after('paid_amount');
            $table->integer('rounding_digits')->default(2)->after('rounding_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['rounding_method', 'rounding_digits']);
        });
    }
};

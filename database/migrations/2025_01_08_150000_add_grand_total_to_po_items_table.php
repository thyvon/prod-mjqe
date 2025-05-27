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
        Schema::table('po_items', function (Blueprint $table) {
            $table->decimal('grand_total', 15, 4)->after('vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_items', function (Blueprint $table) {
            $table->dropColumn('grand_total');
        });
    }
};

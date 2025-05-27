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
        Schema::table('cancellation_items', function (Blueprint $table) {
            $table->decimal('qty', 8, 4)->after('purchase_request_item_id')->default(1.00000000); // Define qty as decimal(15,8)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cancellation_items', function (Blueprint $table) {
            $table->dropColumn('qty'); // Remove qty column
        });
    }
};

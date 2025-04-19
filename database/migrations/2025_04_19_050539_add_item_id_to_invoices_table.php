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
        Schema::table('invoice_attachments', function (Blueprint $table) {
            $table->string('sharepoint_file_id')->nullable()->after('id'); // Add the item_id column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_attachments', function (Blueprint $table) {
            $table->dropColumn('sharepoint_file_id'); // Drop the status column
        });
    }
};

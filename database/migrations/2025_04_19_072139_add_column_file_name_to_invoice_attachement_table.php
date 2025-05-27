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
            $table->string('file_name')->after('file_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_attachments', function (Blueprint $table) {
            $table->dropColumn('file_name'); // Drop the file_name column
        });
    }
};

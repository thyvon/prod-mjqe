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
        Schema::table('clear_statements', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clear_statements', function (Blueprint $table) {
            $table->dropColumn('remark'); // Drop the remark column
        });
    }
};

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
        Schema::table('telegrams', function (Blueprint $table) {
        $table->string('name')->nullable();
        $table->string('photo_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegrams', function (Blueprint $table) {
            $table->dropColumn(['name', 'photo_url']);
        });
    }
};

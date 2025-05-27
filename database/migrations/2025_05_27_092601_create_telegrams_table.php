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
        Schema::create('telegrams', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->text('message')->nullable();
            $table->string('file_url')->nullable();
            $table->enum('type', ['text', 'photo', 'document', 'other'])->default('text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegrams');
    }
};

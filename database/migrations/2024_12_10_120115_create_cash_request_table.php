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
        Schema::create('cash_requests', function (Blueprint $table) {
            $table->id(); // Primary key with auto-increment
            $table->integer('request_type'); // Changed from string to integer
            $table->string('ref_no')->unique(); // Unique reference number
            $table->date('request_date');
            $table->unsignedBigInteger('user_id'); // User ID for relationship
            $table->string('request_by');
            $table->string('position');
            $table->string('id_card');
            $table->string('campus');
            $table->string('division');
            $table->string('department');
            $table->text('description')->nullable();
            $table->integer('currency');
            $table->decimal('exchange_rate', 15, 2); // Adjust precision/scale if needed
            $table->decimal('amount', 15, 2); // Adjust precision/scale if needed
            $table->string('via');
            $table->text('reason')->nullable();
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Index for better query performance if you frequently query by request_type
            $table->index('request_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_requests');
    }
};

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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number');
            $table->date('request_date');
            $table->foreignId('request_by')->constrained('users')->onDelete('cascade'); // Foreign key to the 'users' table
            $table->string('campus');
            $table->string('division');
            $table->string('department');
            $table->string('purpose');
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_cancel')->default(false);
            $table->integer('total_item')->default(0);
            $table->integer('item_po')->default(0);
            $table->integer('item_cancel')->default(0);
            $table->integer('item_purchas')->default(0);
            $table->integer('item_pending')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0.00); // Set precision and scale as needed
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};

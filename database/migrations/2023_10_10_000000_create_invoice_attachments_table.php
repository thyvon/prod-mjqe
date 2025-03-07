<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('file_url');
            $table->timestamps();

            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_attachments');
    }
};

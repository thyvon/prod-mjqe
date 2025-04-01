<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('paid_usd', 23, 15)->default(0)->after('paid_amount');
        });
    }

    public function down()
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('paid_usd');
        });
    }
};

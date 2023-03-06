<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_order_status', function (Blueprint $table) {
            Schema::rename('purchase_order_status', 'purchase_order_statuses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_order_status', function (Blueprint $table) {
            Schema::rename('purchase_order_statuses', 'purchase_order_status');
        });
    }
};

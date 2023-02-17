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
        Schema::table('vendor', function (Blueprint $table) {
            $table->double('latitude')->nulllale();
            $table->double('longitude')->nulllale();
            $table->string('type')->change();
            $table->dropColumn('number_1');
            $table->dropColumn('number_2');
            $table->string('number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendor', function (Blueprint $table) {
            $table->dropcolumn('latitude');
            $table->dropcolumn('longitude');
            $table->integer('type')->change();
            $table->string('number_1');
            $table->string('number_2');
            $table->dropcolumn('number');
        });
    }
};

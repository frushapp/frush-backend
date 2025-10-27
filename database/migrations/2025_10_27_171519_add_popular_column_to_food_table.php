<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPopularColumnToFoodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('food', function (Blueprint $table) {
            $table->enum('is_popular', ['0', '1'])->default('0')->after('status');
            $table->enum('is_newest', ['0', '1'])->default('0')->after('status');
            $table->enum('is_recommended', ['0', '1'])->default('0')->after('status');
            $table->enum('is_trending', ['0', '1'])->default('0')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('food', function (Blueprint $table) {
            //
        });
    }
}

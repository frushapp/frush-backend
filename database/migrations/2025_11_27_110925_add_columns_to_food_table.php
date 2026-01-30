<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddColumnsToFoodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = Schema::getColumnListing('food');
        
        if (!in_array('stock', $columns)) {
            Schema::table('food', function (Blueprint $table) {
                $table->integer('stock')->nullable();
            });
        }
        
        if (!in_array('daily_opening_stock', $columns)) {
            Schema::table('food', function (Blueprint $table) {
                $table->integer('daily_opening_stock')->nullable();
            });
        }
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

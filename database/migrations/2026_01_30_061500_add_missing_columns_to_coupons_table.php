<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = Schema::getColumnListing('coupons');
        
        if (!in_array('coupon_type_cod', $columns)) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->string('coupon_type_cod', 20)->default('BOTH')->nullable();
            });
        }
        
        if (!in_array('description', $columns)) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->text('description')->nullable();
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
        Schema::table('coupons', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPrerequisiteToCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to avoid issues
        $columns = Schema::getColumnListing('coupons');
        
        if (!in_array('prerequisite_coupon_id', $columns)) {
            DB::statement('ALTER TABLE coupons ADD COLUMN prerequisite_coupon_id BIGINT UNSIGNED NULL');
        }
        
        if (!in_array('prerequisite_uses_required', $columns)) {
            DB::statement('ALTER TABLE coupons ADD COLUMN prerequisite_uses_required INT DEFAULT 0');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $columns = Schema::getColumnListing('coupons');
        
        if (in_array('prerequisite_coupon_id', $columns)) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dropColumn('prerequisite_coupon_id');
            });
        }
        
        if (in_array('prerequisite_uses_required', $columns)) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dropColumn('prerequisite_uses_required');
            });
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add order_count to delivery_men table
        if (!Schema::hasColumn('delivery_men', 'order_count')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                $table->integer('order_count')->default(0);
            });
        }

        // Add order_count to restaurants table
        if (!Schema::hasColumn('restaurants', 'order_count')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->integer('order_count')->default(0);
            });
        }

        // Add order_count to food table
        if (!Schema::hasColumn('food', 'order_count')) {
            Schema::table('food', function (Blueprint $table) {
                $table->integer('order_count')->default(0);
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
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn('order_count');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('order_count');
        });

        Schema::table('food', function (Blueprint $table) {
            $table->dropColumn('order_count');
        });
    }
}

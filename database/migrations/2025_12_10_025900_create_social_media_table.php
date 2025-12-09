<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSocialMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('social_media')) {
            Schema::create('social_media', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('link')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        // Seed default business settings if missing
        $icon = DB::table('business_settings')->where('key', 'icon')->first();
        if (!$icon) {
            DB::table('business_settings')->insert([
                'key' => 'icon',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_media');
    }
}

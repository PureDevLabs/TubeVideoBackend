<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKeySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('key_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->mediumInteger('max_video_duration')->default(14400);
            $table->timestamps();
            $table->unique('key_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('key_settings');
    }
}

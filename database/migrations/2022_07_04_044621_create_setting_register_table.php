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
        Schema::create('setting_register', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('thumbnail')->nullable();
            $table->double('total_plus_ubgxu')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('expire_date')->nullable();
            $table->enum('type',['web','app'])->default('app');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setting_register');
    }
};

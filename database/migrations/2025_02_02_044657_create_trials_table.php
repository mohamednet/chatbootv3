<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trials', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('password');
            $table->string('url');
            $table->string('m3u_link');
            $table->string('assigned_user')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trials');
    }
};

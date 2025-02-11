<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('marketing_message_count')->default(0);
            $table->integer('reminder_count_trial')->default(0);
            $table->timestamp('last_reminder_sent')->nullable();
            $table->timestamp('last_marketing_message')->nullable();
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('marketing_message_count');
            $table->dropColumn('reminder_count_trial');
            $table->dropColumn('last_reminder_sent');
            $table->dropColumn('last_marketing_message');
        });
    }
};

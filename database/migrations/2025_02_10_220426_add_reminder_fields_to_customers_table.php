<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('reminder_count_trial')->default(0);
            $table->integer('reminder_count_paid')->default(0);
            $table->integer('marketing_reminder_count')->default(0);
            $table->boolean('non_disturb')->default(false);
            $table->timestamp('last_marketing_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('reminder_count_trial');
            $table->dropColumn('reminder_count_paid');
            $table->dropColumn('marketing_reminder_count');
            $table->dropColumn('non_disturb');
            $table->dropColumn('last_marketing_message');
        });
    }
};

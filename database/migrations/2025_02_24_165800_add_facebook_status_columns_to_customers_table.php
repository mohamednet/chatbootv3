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
            $table->boolean('facebook_messages_disabled')->default(false);
            $table->timestamp('facebook_disabled_at')->nullable();
            $table->string('facebook_disabled_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_messages_disabled',
                'facebook_disabled_at',
                'facebook_disabled_reason'
            ]);
        });
    }
};

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
            $table->boolean('paid_status')->nullable();
            $table->string('subscription_id')->nullable();
            $table->dateTime('subscription_end_date')->nullable();
            $table->dateTime('last_payment_date')->nullable();
            $table->string('subscription_type')->nullable();
            $table->integer('number_of_devices')->nullable();
            $table->string('plan')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->string('payment_method')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'paid_status',
                'subscription_id',
                'subscription_end_date',
                'last_payment_date',
                'subscription_type',
                'number_of_devices',
                'plan',
                'amount',
                'payment_method'
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIboproCredentialsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('ibopro_mac_address', 17)->nullable()->after('facebook_id'); // XX:XX:XX:XX:XX:XX format
            $table->string('ibopro_device_key', 29)->nullable()->after('ibopro_mac_address'); // XXXXX-XXXXX-XXXXX-XXXXX-XXXXX format
            $table->enum('ibopro_credentials_status', ['pending', 'pending_activation', 'activated', 'failed'])->nullable()->after('ibopro_device_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'ibopro_mac_address',
                'ibopro_device_key',
                'ibopro_credentials_status'
            ]);
        });
    }
}

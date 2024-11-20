<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->string('full_sync_schedule')->nullable();
            $table->string('light_sync_schedule')->nullable();
            $table->timestamp('last_full_sync')->nullable();
            $table->timestamp('last_light_sync')->nullable();
        });
    }

    public function down()
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn([
                'full_sync_schedule',
                'light_sync_schedule',
                'last_full_sync',
                'last_light_sync'
            ]);
        });
    }
};

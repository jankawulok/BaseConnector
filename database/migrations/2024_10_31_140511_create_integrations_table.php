<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntegrationsTable extends Migration
{
    public function up()
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->integer('id')->primary()->autoincrement();               // String primary key for integration_id
            $table->string('name');                        // Integration name
            $table->boolean('enabled')->default(true);     // Enabled status
            $table->string('api_key')->unique();           // Unique API key for integration
            $table->string('full_feed_url')->nullable();   // URL for full feed
            $table->string('light_feed_url')->nullable();  // URL for light feed
            $table->json('full_import_definition')->nullable(); // JSON field for full import paths
            $table->json('light_import_definition')->nullable(); // JSON field for light import paths
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('integrations');
    }
}

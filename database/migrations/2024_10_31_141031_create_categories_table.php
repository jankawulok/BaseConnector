<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('auto_id');                 // Auto-incrementing primary key
            $table->string('id');                  // Category ID (string)
            $table->string('integration_id');      // Integration ID for uniqueness
            $table->string('name');                // Category name
            $table->timestamps();

            // Unique composite index on id and integration_id
            $table->unique(['id', 'integration_id']);

            // Foreign key to integrations table
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
}

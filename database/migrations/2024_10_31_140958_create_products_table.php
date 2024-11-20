<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('auto_id');               // Auto-incrementing primary key
            $table->string('id');                // Product ID
            $table->string('integration_id');    // Integration ID
            $table->string('sku')->nullable();
            $table->string('ean')->nullable();
            $table->string('name');
            $table->integer('quantity')->default(0);
            $table->float('price')->default(0.0);
            $table->string('currency', 3)->default('PLN');
            $table->integer('tax')->default(0);
            $table->float('weight')->default(0.0);
            $table->float('height')->default(0.0);
            $table->float('length')->default(0.0);
            $table->float('width')->default(0.0);
            $table->text('description')->nullable();
            $table->text('description_extra1')->nullable();
            $table->text('description_extra2')->nullable();
            $table->text('description_extra3')->nullable();
            $table->text('description_extra4')->nullable();
            $table->string('man_name')->nullable();
            $table->string('location')->nullable();
            $table->string('url')->nullable();
            $table->json('images')->nullable();
            $table->json('features')->nullable();
            $table->json('variants')->nullable();
            $table->integer('delivery_time')->default(1);
            $table->timestamps();

            // Unique composite index on id and integration_id
            $table->unique(['id', 'integration_id']);

            // Foreign key to integrations table
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}

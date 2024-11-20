<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryProductTable extends Migration
{
    public function up()
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->unsignedBigInteger('product_auto_id'); // References auto_id from Product
            $table->unsignedBigInteger('category_auto_id');                 // References id from Category

            // Composite primary key for uniqueness
            $table->primary(['product_auto_id', 'category_auto_id']);

            // Foreign keys
            $table->foreign('product_auto_id')->references('auto_id')->on('products')->onDelete('cascade');
            $table->foreign('category_auto_id')->references('auto_id')->on('categories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_product');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_auto_id')->constrained('products', 'auto_id')->onDelete('cascade');
            $table->string('field_name'); // 'price' or 'quantity'
            $table->decimal('old_value', 10, 2);
            $table->decimal('new_value', 10, 2);
            $table->string('variant_id')->nullable(); // For variant changes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_histories');
    }
};

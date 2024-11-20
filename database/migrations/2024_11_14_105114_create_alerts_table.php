<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['price_change', 'stock_change', 'product_added', 'product_removed']);
            $table->json('condition')->comment('JSON containing alert conditions (percentage, threshold, etc.)');
            $table->json('filters')->nullable()->comment('JSON containing product filters (sku pattern, price range, etc.)');
            $table->string('notification_email');
            $table->string('notification_schedule')->default('0 */6 * * *')->comment('Cron expression for notification frequency');
            $table->timestamp('last_notification_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for better query performance
            $table->index('is_active');
            $table->index('last_notification_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('alerts');
    }
};

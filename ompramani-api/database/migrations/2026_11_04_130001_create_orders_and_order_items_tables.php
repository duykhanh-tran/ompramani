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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('order_number', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('customer_name', 255);
            $table->string('customer_phone', 30);
            $table->string('customer_email', 255)->nullable();
            $table->string('shipping_address', 500);
            $table->string('shipping_province', 100);
            $table->unsignedBigInteger('shipping_fee_vnd')->default(0);
            $table->unsignedBigInteger('subtotal_vnd');
            $table->unsignedBigInteger('total_vnd');
            $table->string('currency', 10)->default('VND');
            $table->string('order_status', 30)->default('pending');
            $table->string('payment_method', 30)->default('cod');
            $table->string('payment_status', 30)->default('unpaid');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['order_status', 'payment_status']);
            $table->index(['user_id', 'idempotency_key']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_public_id', 36);
            $table->string('product_name_snapshot', 255);
            $table->string('product_sku_snapshot', 100)->nullable();
            $table->string('product_image_snapshot', 500)->nullable();
            $table->unsignedBigInteger('unit_price_vnd');
            $table->integer('quantity');
            $table->unsignedBigInteger('line_total_vnd');
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

<?php

declare(strict_types=1);

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
        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('type', 30)->default('adjustment'); // adjustment, restock, stocktake, order_placed, order_cancelled
                $table->integer('quantity_before');
                $table->integer('quantity_after');
                $table->integer('change'); // signed delta: +5, -3, etc.
                $table->string('reason', 255)->nullable();
                $table->string('actor_type', 20)->default('admin'); // admin, system, customer
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['product_id', 'created_at']);
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('order_refunds')) {
            Schema::create('order_refunds', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('refund_number', 32)->unique();
                $table->unsignedBigInteger('amount_vnd');
                $table->string('status', 30)->default('pending'); // pending, completed, failed
                $table->string('reason', 255)->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
                $table->index(['status', 'refunded_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_refunds');
        Schema::dropIfExists('inventory_movements');
    }
};

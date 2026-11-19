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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_vnd')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('inventory_mode', 30)->default('not_tracked');
            $table->string('status', 30)->default('draft');
            $table->boolean('is_active')->default(true);
            $table->string('cover_image')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
            $table->index('category_id');
            $table->index('published_at');
            $table->index('price_vnd');
            $table->index('normalized_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};

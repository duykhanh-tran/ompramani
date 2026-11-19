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
        Schema::create('sutras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tradition_id')
                ->constrained('traditions')
                ->restrictOnDelete();

            $table->string('title');
            $table->string('slug')->unique();

            $table->string('original_title')->nullable();

            $table->text('short_description')->nullable();

            $table->string('thumbnail')->nullable();

            $table->string('status', 30)->default('draft');

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['tradition_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sutras');
    }
};

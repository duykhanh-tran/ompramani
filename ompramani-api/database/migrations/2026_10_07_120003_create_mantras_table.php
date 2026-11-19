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
        Schema::create('mantras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tradition_id')->nullable()->constrained('traditions')->nullOnDelete();
            $table->foreignId('buddhist_figure_id')->nullable()->constrained('buddhist_figures')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('original_title')->nullable();
            $table->text('short_description')->nullable();
            $table->text('practice_guidance')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantras');
    }
};

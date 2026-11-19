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
        Schema::create('buddhist_figures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tradition_id')->nullable()->constrained('traditions')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('original_name')->nullable();
            $table->string('figure_type')->default('bodhisattva');
            $table->text('short_description')->nullable();
            $table->text('biography')->nullable();
            $table->text('great_vows')->nullable();
            $table->text('iconography')->nullable();
            $table->text('practice_guidance')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
            $table->index('figure_type');
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddhist_figures');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely drops Daily Dharma Verse and Verse Assignment tables for Task 29 complete removal.
     */
    public function up(): void
    {
        Schema::dropIfExists('daily_verse_assignments');
        Schema::dropIfExists('dharma_verses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('dharma_verses', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('content_kind', 64)->default('scripture_excerpt');
            $table->text('verse_text');
            $table->text('original_text')->nullable();
            $table->text('reflection_text')->nullable();

            // Source & Attribution
            $table->string('source_title');
            $table->string('source_reference')->nullable();
            $table->string('source_url', 1024)->nullable();
            $table->string('translator_name')->nullable();
            $table->string('translation_title')->nullable();
            $table->string('attribution')->nullable();
            $table->text('license_information')->nullable();

            // Relationships to Sutra library & Tradition
            $table->foreignId('sutra_id')->nullable()->constrained('sutras')->nullOnDelete();
            $table->foreignId('sutra_version_id')->nullable()->constrained('sutra_versions')->nullOnDelete();
            $table->foreignId('sutra_section_id')->nullable()->constrained('sutra_sections')->nullOnDelete();
            $table->foreignId('tradition_id')->nullable()->constrained('traditions')->nullOnDelete();

            // Editorial Workflow & Rotation
            $table->string('status', 32)->default('draft');
            $table->boolean('is_rotatable')->default(true);
            $table->integer('sort_order')->default(0);

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'published_at']);
            $table->index(['status', 'is_rotatable']);
            $table->index('content_kind');
            $table->index('tradition_id');
            $table->index('sutra_id');
        });

        Schema::create('daily_verse_assignments', function (Blueprint $table): void {
            $table->id();
            $table->date('display_date')->unique();
            $table->foreignId('dharma_verse_id')->constrained('dharma_verses')->cascadeOnDelete();
            $table->string('assignment_source', 32)->default('rotation');
            $table->boolean('is_editorial_override')->default(false);
            $table->string('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('dharma_verse_id');
            $table->index('assignment_source');
        });
    }
};

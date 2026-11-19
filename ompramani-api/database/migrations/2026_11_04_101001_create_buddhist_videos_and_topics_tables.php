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
        Schema::create('video_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('buddhist_videos', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('category')->index();
            $table->string('provider')->default('youtube')->index();
            $table->string('provider_video_id')->nullable();
            $table->string('canonical_source_url', 1000);
            $table->string('source_title')->nullable();
            $table->string('source_channel_name')->nullable();
            $table->string('speaker_name')->nullable()->index();
            $table->integer('duration_seconds')->nullable();
            $table->string('language_code', 10)->default('vi');
            $table->text('thumbnail_url')->nullable();
            $table->string('copyright_status')->default('pending_review');
            $table->string('embed_permission')->default('external_only');
            $table->string('editorial_status')->default('draft')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('normalized_title')->nullable()->index();
            $table->string('normalized_speaker')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider', 'provider_video_id']);
            $table->index(['editorial_status', 'published_at']);
            $table->index(['category', 'editorial_status']);
        });

        Schema::create('buddhist_video_topic', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buddhist_video_id')->constrained('buddhist_videos')->cascadeOnDelete();
            $table->foreignId('video_topic_id')->constrained('video_topics')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['buddhist_video_id', 'video_topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddhist_video_topic');
        Schema::dropIfExists('buddhist_videos');
        Schema::dropIfExists('video_topics');
    }
};

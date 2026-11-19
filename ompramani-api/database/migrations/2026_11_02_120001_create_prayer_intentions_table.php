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
        Schema::create('prayer_intentions', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->text('body');
            $table->string('category', 50)->nullable()->index();
            $table->string('visibility', 20)->default('private');
            $table->string('moderation_status', 20)->default('not_required');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason_code', 50)->nullable();
            $table->text('user_rejection_note')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for optimal query performance
            $table->index(['visibility', 'moderation_status', 'published_at'], 'idx_prayer_public_approved');
            $table->index(['user_id', 'created_at'], 'idx_prayer_user_created');
            $table->index(['moderation_status', 'submitted_at'], 'idx_prayer_moderation_queue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_intentions');
    }
};

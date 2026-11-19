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
        Schema::create('prayer_responses', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->foreignId('prayer_intention_id')->constrained('prayer_intentions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('moderation_status', 20)->default('pending');
            $table->unsignedInteger('submission_revision')->default(1);
            $table->timestamp('parent_publication_revision')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason_code', 50)->nullable();
            $table->text('user_rejection_note')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast public listing, personal history, and moderation queue
            $table->index(['prayer_intention_id', 'moderation_status', 'published_at'], 'idx_resp_public_approved');
            $table->index(['user_id', 'created_at'], 'idx_resp_user_created');
            $table->index(['moderation_status', 'submitted_at'], 'idx_resp_moderation_queue');
        });

        Schema::create('prayer_response_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prayer_response_id')->constrained('prayer_responses')->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 50);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_resolution_notes')->nullable();
            $table->timestamps();

            // Prevent spam duplicate open reports by the same user for the same response
            $table->unique(['prayer_response_id', 'reporter_user_id'], 'uniq_response_reporter_report');
            $table->index(['status', 'created_at'], 'idx_reports_status_created');
        });

        Schema::create('prayer_response_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prayer_response_id')->constrained('prayer_responses')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('reason_code', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['prayer_response_id', 'created_at'], 'idx_resp_audit_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_response_audits');
        Schema::dropIfExists('prayer_response_reports');
        Schema::dropIfExists('prayer_responses');
    }
};

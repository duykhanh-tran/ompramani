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
        Schema::create('practice_reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_reminder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_for_utc');
            $table->unsignedInteger('schedule_revision')->default(1);
            $table->string('channel', 32)->default('database');
            $table->string('status', 32)->default('pending'); // pending, processing, sent, skipped_quiet_hours, skipped_disabled, skipped_late, failed
            $table->uuid('notification_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('failure_reason_code', 64)->nullable();
            $table->timestamps();

            // Strict unique constraint preventing duplicate delivery of the same occurrence
            $table->unique(
                ['practice_reminder_id', 'scheduled_for_utc', 'schedule_revision', 'channel'],
                'reminder_delivery_unique_occ'
            );

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_reminder_deliveries');
    }
};

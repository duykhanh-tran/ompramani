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
        Schema::create('practice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('practice_type', 32);
            $table->string('title', 100)->nullable();
            $table->string('schedule_type', 16); // once, daily, weekly
            $table->string('local_time', 5); // HH:MM
            $table->string('timezone', 64)->default('Asia/Ho_Chi_Minh');
            $table->date('local_date')->nullable(); // For once schedule
            $table->json('weekdays')->nullable(); // For weekly schedule: [1, 3, 5] (ISO 1=Mon, 7=Sun)
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('schedule_revision')->default(1);
            $table->timestamp('next_due_at_utc')->nullable();
            $table->timestamp('last_scheduled_at_utc')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast scheduler queries and user listings
            $table->index(['user_id', 'is_enabled']);
            $table->index(['is_enabled', 'next_due_at_utc'], 'practice_reminders_due_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_reminders');
    }
};

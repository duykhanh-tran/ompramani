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
        Schema::create('practice_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practice_plan_id')->constrained('practice_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('practice_type', 64);
            $table->string('title', 150)->nullable();
            $table->string('schedule_type', 32)->default('weekly'); // once, weekly
            $table->date('local_date')->nullable(); // for once
            $table->string('local_time', 8)->nullable(); // HH:MM, nullable for flexible times
            $table->jsonb('weekdays')->nullable(); // ISO 1=Monday ... 7=Sunday
            $table->unsignedInteger('target_duration_seconds')->nullable();
            $table->unsignedInteger('target_repetitions')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('schedule_revision')->default(1);
            $table->foreignId('practice_reminder_id')->nullable()->constrained('practice_reminders')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['practice_plan_id', 'is_enabled']);
            $table->index(['user_id', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_plan_items');
    }
};

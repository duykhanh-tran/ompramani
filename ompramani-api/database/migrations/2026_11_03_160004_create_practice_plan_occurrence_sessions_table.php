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
        Schema::create('practice_plan_occurrence_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practice_plan_item_id')->constrained('practice_plan_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('occurrence_local_date');
            $table->unsignedInteger('schedule_revision')->default(1);
            $table->foreignId('practice_session_id')->constrained('practice_sessions')->cascadeOnDelete();
            $table->timestamp('linked_at');
            $table->timestamps();

            // Enforce that each PracticeSession can only be linked to one occurrence
            $table->unique('practice_session_id', 'plan_occ_session_unique_session');

            // Enforce one primary linked session per occurrence
            $table->unique(
                ['practice_plan_item_id', 'occurrence_local_date'],
                'plan_occ_session_unique_item_date'
            );

            $table->index(['user_id', 'occurrence_local_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_plan_occurrence_sessions');
    }
};

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
        Schema::create('practice_plan_occurrence_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practice_plan_item_id')->constrained('practice_plan_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('local_date');
            $table->unsignedInteger('schedule_revision')->default(1);
            $table->string('action', 32)->default('skip'); // skip
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(
                ['practice_plan_item_id', 'local_date', 'action'],
                'plan_occ_override_unique'
            );
            $table->index(['user_id', 'local_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_plan_occurrence_overrides');
    }
};

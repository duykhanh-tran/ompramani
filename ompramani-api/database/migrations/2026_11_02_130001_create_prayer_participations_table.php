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
        Schema::create('prayer_participations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prayer_intention_id')->constrained('prayer_intentions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('participated_at')->useCurrent();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            // Strict uniqueness: exactly one record per (prayer_intention, user) pair
            $table->unique(['prayer_intention_id', 'user_id'], 'uniq_prayer_user_participation');

            // Fast active count queries and user active participation queries
            $table->index(['prayer_intention_id', 'withdrawn_at'], 'idx_prayer_participation_active');
            $table->index(['user_id', 'withdrawn_at'], 'idx_user_participation_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_participations');
    }
};

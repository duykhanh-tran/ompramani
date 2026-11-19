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
        Schema::create('sutra_reading_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sutra_id')->constrained('sutras')->cascadeOnDelete();
            $table->foreignId('sutra_version_id')->constrained('sutra_versions')->cascadeOnDelete();
            $table->foreignId('sutra_section_id')->constrained('sutra_sections')->cascadeOnDelete();
            $table->timestamp('last_read_at');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'sutra_version_id'], 'uq_sutra_reading_user_version');
            $table->index(['user_id', 'last_read_at'], 'idx_sutra_reading_user_last_read');
            $table->index(['sutra_id'], 'idx_sutra_reading_sutra');
            $table->index(['sutra_section_id'], 'idx_sutra_reading_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sutra_reading_progress');
    }
};

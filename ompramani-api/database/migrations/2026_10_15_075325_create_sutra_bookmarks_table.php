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
        Schema::create('sutra_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20); // 'sutra' or 'section'
            $table->foreignId('sutra_id')->constrained('sutras')->cascadeOnDelete();
            $table->foreignId('sutra_version_id')->nullable()->constrained('sutra_versions')->cascadeOnDelete();
            $table->foreignId('sutra_section_id')->nullable()->constrained('sutra_sections')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at'], 'idx_sutra_bookmarks_user_type');
            $table->index(['sutra_id'], 'idx_sutra_bookmarks_sutra');
            $table->index(['sutra_section_id'], 'idx_sutra_bookmarks_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sutra_bookmarks');
    }
};

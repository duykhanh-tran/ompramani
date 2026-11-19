<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('practice_type', 50);
            $table->string('status', 30)->default('active');
            $table->string('subject_type', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('active_duration_seconds')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('progress_data')->nullable();
            $table->string('client_session_key', 100)->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'started_at']);
            $table->index(['practice_type', 'status']);
            $table->unique(['user_id', 'client_session_key']);
        });

        // Concurrency guard: A user can only have at most one open ('active' or 'paused') practice session at a time.
        // Supported natively in both PostgreSQL and SQLite (>= 3.8.0).
        DB::statement("CREATE UNIQUE INDEX practice_sessions_user_open_unique ON practice_sessions (user_id) WHERE status IN ('active', 'paused')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS practice_sessions_user_open_unique');
        Schema::dropIfExists('practice_sessions');
    }
};

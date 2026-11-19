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
        Schema::table('practice_sessions', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'ended_at'], 'practice_sessions_user_status_ended_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $table): void {
            $table->dropIndex('practice_sessions_user_status_ended_idx');
        });
    }
};

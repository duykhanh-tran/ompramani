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
        Schema::table('mantras', function (Blueprint $table): void {
            if (!Schema::hasColumn('mantras', 'audio_path')) {
                $table->string('audio_path')->nullable()->after('thumbnail');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantras', function (Blueprint $table): void {
            if (Schema::hasColumn('mantras', 'audio_path')) {
                $table->dropColumn('audio_path');
            }
        });
    }
};

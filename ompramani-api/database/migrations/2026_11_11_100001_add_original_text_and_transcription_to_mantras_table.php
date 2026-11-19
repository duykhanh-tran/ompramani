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
            $table->text('original_text')->nullable()->after('short_description');
            $table->text('vietnamese_transcription')->nullable()->after('original_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantras', function (Blueprint $table): void {
            $table->dropColumn(['original_text', 'vietnamese_transcription']);
        });
    }
};

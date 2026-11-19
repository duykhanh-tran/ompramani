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
        Schema::create('audio_playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained('audio_playlists')->cascadeOnDelete();
            $table->foreignId('audio_track_id')->constrained('audio_tracks')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['playlist_id', 'audio_track_id']);
            $table->index(['playlist_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_playlist_items');
    }
};

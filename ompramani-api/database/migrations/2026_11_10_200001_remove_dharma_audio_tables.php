<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restores standalone recitation_audios for Task 22 and drops Dharma Audio tables.
     */
    public function up(): void
    {
        // 1. Remove polymorphic favorite records referencing 'audio'
        if (Schema::hasTable('user_favorites')) {
            DB::table('user_favorites')->where('favoritable_type', 'audio')->delete();
        }

        // 2. Ensure recitation_audios table exists independently for Chanting Task 22
        if (! Schema::hasTable('recitation_audios')) {
            Schema::create('recitation_audios', function (Blueprint $table): void {
                $table->id();
                $table->string('audioable_type');
                $table->unsignedBigInteger('audioable_id');
                $table->string('title');
                $table->string('file_path');
                $table->string('file_url')->nullable();
                $table->string('mime_type')->default('audio/mpeg');
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('performer')->nullable();
                $table->string('source')->nullable();
                $table->string('license_information')->nullable();
                $table->string('status')->default('draft');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['audioable_type', 'audioable_id', 'status'], 'recit_audios_audioable_status_idx');
            });
        }

        // 3. If audio_tracks exists and had recitation audios, migrate them to recitation_audios
        if (Schema::hasTable('audio_tracks') && Schema::hasColumn('audio_tracks', 'audioable_type')) {
            $tracks = DB::table('audio_tracks')->whereNotNull('audioable_type')->get();
            foreach ($tracks as $track) {
                DB::table('recitation_audios')->insertOrIgnore([
                    'id' => $track->id,
                    'audioable_type' => $track->audioable_type,
                    'audioable_id' => $track->audioable_id,
                    'title' => $track->title,
                    'file_path' => $track->file_path,
                    'file_url' => $track->file_url,
                    'mime_type' => $track->mime_type ?? 'audio/mpeg',
                    'duration_seconds' => $track->duration_seconds,
                    'performer' => $track->performer,
                    'source' => $track->source,
                    'license_information' => $track->license_information,
                    'status' => $track->status ?? 'draft',
                    'sort_order' => $track->sort_order ?? 0,
                    'created_at' => $track->created_at,
                    'updated_at' => $track->updated_at,
                ]);
            }
        }

        // 4. Drop Dharma Audio tables in reverse dependency order
        Schema::dropIfExists('audio_playlist_items');
        Schema::dropIfExists('audio_playlists');
        Schema::dropIfExists('audio_tracks');
        Schema::dropIfExists('audio_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('audio_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audio_tracks', function (Blueprint $table): void {
            $table->id();
            $table->string('audioable_type')->nullable();
            $table->unsignedBigInteger('audioable_id')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('audio_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_url')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('mime_type')->default('audio/mpeg');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('performer')->nullable();
            $table->string('source')->nullable();
            $table->string('license_information')->nullable();
            $table->string('status')->default('draft');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('audio_playlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('visibility')->default('public');
            $table->string('status')->default('published');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('audio_playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained('audio_playlists')->cascadeOnDelete();
            $table->foreignId('audio_track_id')->constrained('audio_tracks')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['playlist_id', 'audio_track_id']);
        });
    }
};

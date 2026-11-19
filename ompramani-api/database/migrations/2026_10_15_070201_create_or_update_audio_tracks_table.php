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
        if (Schema::hasTable('recitation_audios') && ! Schema::hasTable('audio_tracks')) {
            Schema::rename('recitation_audios', 'audio_tracks');
        }

        if (! Schema::hasTable('audio_tracks')) {
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
                $table->string('language')->nullable();
                $table->string('source')->nullable();
                $table->string('attribution')->nullable();
                $table->string('license_type')->nullable();
                $table->text('license_details')->nullable();
                $table->string('license_information')->nullable();
                $table->string('status')->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['audioable_type', 'audioable_id', 'status'], 'audio_tracks_audioable_status_idx');
                $table->index(['status', 'published_at']);
                $table->index(['category_id', 'status']);
            });
        } else {
            Schema::table('audio_tracks', function (Blueprint $table): void {
                if (! Schema::hasColumn('audio_tracks', 'category_id')) {
                    $table->foreignId('category_id')->nullable()->after('audioable_id')->constrained('audio_categories')->nullOnDelete();
                }
                if (! Schema::hasColumn('audio_tracks', 'slug')) {
                    $table->string('slug')->nullable()->unique()->after('title');
                }
                if (! Schema::hasColumn('audio_tracks', 'description')) {
                    $table->text('description')->nullable()->after('slug');
                }
                if (! Schema::hasColumn('audio_tracks', 'cover_image')) {
                    $table->string('cover_image')->nullable()->after('file_url');
                }
                if (! Schema::hasColumn('audio_tracks', 'language')) {
                    $table->string('language')->nullable()->after('performer');
                }
                if (! Schema::hasColumn('audio_tracks', 'attribution')) {
                    $table->string('attribution')->nullable()->after('source');
                }
                if (! Schema::hasColumn('audio_tracks', 'license_type')) {
                    $table->string('license_type')->nullable()->after('attribution');
                }
                if (! Schema::hasColumn('audio_tracks', 'license_details')) {
                    $table->text('license_details')->nullable()->after('license_type');
                }
                if (! Schema::hasColumn('audio_tracks', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('audio_tracks', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('published_at');
                }
            });

            // Modify audioable columns to nullable if they were not nullable
            Schema::table('audio_tracks', function (Blueprint $table): void {
                $table->string('audioable_type')->nullable()->change();
                $table->unsignedBigInteger('audioable_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audio_tracks')) {
            Schema::dropIfExists('audio_tracks');
        }
    }
};

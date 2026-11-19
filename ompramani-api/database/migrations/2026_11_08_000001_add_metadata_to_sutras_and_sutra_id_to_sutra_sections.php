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
        // 1. Add translation and source metadata to sutras table
        Schema::table('sutras', function (Blueprint $table) {
            $table->string('translator')->nullable()->after('original_title');
            $table->string('language', 10)->default('vi')->after('translator');
            $table->string('publisher')->nullable()->after('language');
            $table->string('publication_year', 30)->nullable()->after('publisher');
            $table->text('source')->nullable()->after('publication_year');
            $table->text('notes')->nullable()->after('source');
        });

        // 2. Add direct sutra_id to sutra_sections table and make sutra_version_id nullable
        Schema::table('sutra_sections', function (Blueprint $table) {
            $table->foreignId('sutra_id')
                ->nullable()
                ->after('id')
                ->constrained('sutras')
                ->cascadeOnDelete();

            $table->foreignId('sutra_version_id')->nullable()->change();

            $table->index(['sutra_id', 'position']);
        });

        // 3. Make sutra_version_id nullable in reading progress and bookmarks for single-version flow
        Schema::table('sutra_reading_progress', function (Blueprint $table) {
            $table->foreignId('sutra_version_id')->nullable()->change();
        });

        Schema::table('sutra_bookmarks', function (Blueprint $table) {
            $table->foreignId('sutra_version_id')->nullable()->change();
        });

        // 4. Safe data backfill from existing versions
        // Backfill sutra_id on sections
        DB::statement('
            UPDATE sutra_sections
            SET sutra_id = sutra_versions.sutra_id
            FROM sutra_versions
            WHERE sutra_sections.sutra_version_id = sutra_versions.id
              AND sutra_sections.sutra_id IS NULL
        ');

        // Backfill metadata on sutras from primary version (is_default = true or highest id)
        $sutras = DB::table('sutras')->get();
        foreach ($sutras as $sutra) {
            $version = DB::table('sutra_versions')
                ->where('sutra_id', $sutra->id)
                ->where('status', 'published')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if (! $version) {
                $version = DB::table('sutra_versions')
                    ->where('sutra_id', $sutra->id)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();
            }

            if ($version) {
                DB::table('sutras')->where('id', $sutra->id)->update([
                    'translator' => $version->translator,
                    'language' => $version->language ?? 'vi',
                    'publisher' => $version->publisher,
                    'publication_year' => $version->publication_year,
                    'source' => $version->source,
                    'notes' => $version->notes,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sutra_sections', function (Blueprint $table) {
            $table->dropForeign(['sutra_id']);
            $table->dropIndex(['sutra_id', 'position']);
            $table->dropColumn('sutra_id');
        });

        Schema::table('sutras', function (Blueprint $table) {
            $table->dropColumn([
                'translator',
                'language',
                'publisher',
                'publication_year',
                'source',
                'notes',
            ]);
        });
    }
};

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
        Schema::table('sutras', function (Blueprint $table) {
            $table->index(['status', 'published_at']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent;');
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

            DB::statement('CREATE INDEX IF NOT EXISTS sutras_title_trgm ON sutras USING gin (title gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS sutras_original_title_trgm ON sutras USING gin (original_title gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS sutras_short_description_trgm ON sutras USING gin (short_description gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS sutra_sections_content_trgm ON sutra_sections USING gin (content gin_trgm_ops);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS sutras_title_trgm;');
            DB::statement('DROP INDEX IF EXISTS sutras_original_title_trgm;');
            DB::statement('DROP INDEX IF EXISTS sutras_short_description_trgm;');
            DB::statement('DROP INDEX IF EXISTS sutra_sections_content_trgm;');
        }

        Schema::table('sutras', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent;');
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

            DB::statement('CREATE INDEX IF NOT EXISTS buddhist_figures_name_trgm ON buddhist_figures USING gin (name gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS buddhist_figures_orig_name_trgm ON buddhist_figures USING gin (original_name gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS buddhist_figures_short_desc_trgm ON buddhist_figures USING gin (short_description gin_trgm_ops);');

            DB::statement('CREATE INDEX IF NOT EXISTS mantras_title_trgm ON mantras USING gin (title gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS mantras_orig_title_trgm ON mantras USING gin (original_title gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS mantras_short_desc_trgm ON mantras USING gin (short_description gin_trgm_ops);');

            DB::statement('CREATE INDEX IF NOT EXISTS mantra_versions_content_trgm ON mantra_versions USING gin (content gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS mantra_versions_transliteration_trgm ON mantra_versions USING gin (transliteration gin_trgm_ops);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS buddhist_figures_name_trgm;');
            DB::statement('DROP INDEX IF EXISTS buddhist_figures_orig_name_trgm;');
            DB::statement('DROP INDEX IF EXISTS buddhist_figures_short_desc_trgm;');

            DB::statement('DROP INDEX IF EXISTS mantras_title_trgm;');
            DB::statement('DROP INDEX IF EXISTS mantras_orig_title_trgm;');
            DB::statement('DROP INDEX IF EXISTS mantras_short_desc_trgm;');

            DB::statement('DROP INDEX IF EXISTS mantra_versions_content_trgm;');
            DB::statement('DROP INDEX IF EXISTS mantra_versions_transliteration_trgm;');
        }
    }
};

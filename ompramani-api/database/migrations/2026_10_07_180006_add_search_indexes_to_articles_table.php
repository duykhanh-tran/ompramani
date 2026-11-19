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

            DB::statement('CREATE INDEX IF NOT EXISTS articles_title_trgm ON articles USING gin (title gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS articles_excerpt_trgm ON articles USING gin (excerpt gin_trgm_ops);');
            DB::statement('CREATE INDEX IF NOT EXISTS articles_content_trgm ON articles USING gin (content gin_trgm_ops);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS articles_title_trgm;');
            DB::statement('DROP INDEX IF EXISTS articles_excerpt_trgm;');
            DB::statement('DROP INDEX IF EXISTS articles_content_trgm;');
        }
    }
};

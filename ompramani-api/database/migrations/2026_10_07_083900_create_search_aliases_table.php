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
        Schema::create('search_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->string('alias');
            $table->string('normalized_alias');
            $table->timestamps();

            $table->index(['searchable_type', 'searchable_id']);
            $table->index('alias');
            $table->index('normalized_alias');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');
            DB::statement('CREATE INDEX IF NOT EXISTS search_aliases_normalized_alias_trgm ON search_aliases USING gin (normalized_alias gin_trgm_ops);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS search_aliases_normalized_alias_trgm;');
        }

        Schema::dropIfExists('search_aliases');
    }
};

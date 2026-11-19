<?php

use App\Services\Search\SearchNormalizer;
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
        Schema::table('temples', function (Blueprint $table): void {
            $table->string('normalized_name')->nullable()->index();
            $table->text('normalized_alternative_names')->nullable();
        });

        // Add pg_trgm index if on PostgreSQL and extension exists
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');
            DB::statement('CREATE INDEX IF NOT EXISTS temples_normalized_name_trgm ON temples USING gin (normalized_name gin_trgm_ops);');
        }

        // Backfill existing records with normalized search keys
        if (DB::table('temples')->exists()) {
            foreach (DB::table('temples')->get() as $row) {
                $altText = '';
                if (! empty($row->alternative_names)) {
                    $decoded = json_decode((string) $row->alternative_names, true);
                    if (is_array($decoded)) {
                        $extracted = array_map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : (string) $item, $decoded);
                        $altText = implode(' ', array_filter($extracted));
                    }
                }

                DB::table('temples')->where('id', $row->id)->update([
                    'normalized_name' => SearchNormalizer::normalize($row->name),
                    'normalized_alternative_names' => SearchNormalizer::normalize($altText),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS temples_normalized_name_trgm;');
        }

        Schema::table('temples', function (Blueprint $table): void {
            $table->dropIndex(['normalized_name']);
            $table->dropColumn(['normalized_name', 'normalized_alternative_names']);
        });
    }
};

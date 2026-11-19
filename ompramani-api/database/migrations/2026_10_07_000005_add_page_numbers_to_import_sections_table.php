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
        Schema::table('import_sections', function (Blueprint $table): void {
            $table->unsignedInteger('source_page_start')->nullable()->after('heading_level');
            $table->unsignedInteger('source_page_end')->nullable()->after('source_page_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_sections', function (Blueprint $table): void {
            $table->dropColumn(['source_page_start', 'source_page_end']);
        });
    }
};

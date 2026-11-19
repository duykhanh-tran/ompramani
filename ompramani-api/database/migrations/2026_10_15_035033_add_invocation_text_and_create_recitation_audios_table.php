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
        if (Schema::hasTable('buddhist_figures') && ! Schema::hasColumn('buddhist_figures', 'invocation_text')) {
            Schema::table('buddhist_figures', function (Blueprint $table): void {
                $table->string('invocation_text')->nullable()->after('original_name');
            });
        }

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
                $table->timestamps();

                $table->index(['audioable_type', 'audioable_id', 'status'], 'recitation_audios_audioable_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recitation_audios');

        if (Schema::hasTable('buddhist_figures') && Schema::hasColumn('buddhist_figures', 'invocation_text')) {
            Schema::table('buddhist_figures', function (Blueprint $table): void {
                $table->dropColumn('invocation_text');
            });
        }
    }
};

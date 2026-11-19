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
        Schema::table('sutra_versions', function (Blueprint $table): void {
            $table->foreignId('source_document_id')
                ->nullable()
                ->after('sutra_id')
                ->constrained('source_documents')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sutra_versions', function (Blueprint $table): void {
            $table->dropForeign(['source_document_id']);
            $table->dropColumn('source_document_id');
        });
    }
};

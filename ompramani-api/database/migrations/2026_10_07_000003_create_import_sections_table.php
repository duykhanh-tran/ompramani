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
        Schema::create('import_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_import_id')->constrained('document_imports')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('import_sections')->cascadeOnDelete();

            $table->string('type', 30)->default('paragraph');
            $table->string('title')->nullable();
            $table->text('content')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('source_index')->nullable();
            $table->unsignedInteger('heading_level')->nullable();

            $table->boolean('is_included')->default(true);
            $table->timestamps();

            $table->index(['document_import_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_sections');
    }
};

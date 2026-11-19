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
        Schema::create('sutra_sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sutra_version_id')
                ->constrained('sutra_versions')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('sutra_sections')
                ->cascadeOnDelete();

            $table->string('title')->nullable();

            $table->string('slug')->nullable();

            $table->text('content')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->unsignedInteger('source_page_start')->nullable();
            $table->unsignedInteger('source_page_end')->nullable();

            $table->timestamps();

            $table->index(['sutra_version_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sutra_sections');
    }
};

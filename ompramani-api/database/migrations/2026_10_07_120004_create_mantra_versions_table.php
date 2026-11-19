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
        Schema::create('mantra_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mantra_id')->constrained('mantras')->cascadeOnDelete();
            $table->string('name');
            $table->string('language', 30)->default('sa');
            $table->string('script', 30)->nullable();
            $table->text('content');
            $table->text('transliteration')->nullable();
            $table->text('translation')->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('status');
            $table->index('language');
            $table->index(['mantra_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantra_versions');
    }
};

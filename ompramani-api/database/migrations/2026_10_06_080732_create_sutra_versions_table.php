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
        Schema::create('sutra_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sutra_id')
                ->constrained('sutras')
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('translator')->nullable();

            $table->string('language', 50)->default('vi');

            $table->string('publisher')->nullable();

            $table->string('publication_year', 20)->nullable();

            $table->text('source')->nullable();

            $table->text('notes')->nullable();

            $table->boolean('is_default')->default(false);

            $table->string('status', 30)->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sutra_versions');
    }
};

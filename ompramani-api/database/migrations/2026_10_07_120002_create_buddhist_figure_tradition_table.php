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
        Schema::create('buddhist_figure_tradition', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buddhist_figure_id')->constrained('buddhist_figures')->cascadeOnDelete();
            $table->foreignId('tradition_id')->constrained('traditions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['buddhist_figure_id', 'tradition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddhist_figure_tradition');
    }
};

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
        Schema::create('temple_traditions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('temple_id')->constrained('temples')->cascadeOnDelete();
            $table->foreignId('tradition_id')->constrained('traditions')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['temple_id', 'tradition_id']);
            $table->index(['tradition_id', 'temple_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temple_traditions');
    }
};

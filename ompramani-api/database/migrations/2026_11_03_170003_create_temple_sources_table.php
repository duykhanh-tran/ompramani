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
        Schema::create('temple_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('temple_id')->constrained('temples')->cascadeOnDelete();
            $table->string('source_title');
            $table->string('source_url', 500)->nullable();
            $table->string('source_type')->default('other');
            $table->text('reference_note')->nullable();
            $table->json('verified_fields')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['temple_id', 'source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temple_sources');
    }
};

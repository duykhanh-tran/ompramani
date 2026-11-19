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
        Schema::create('temple_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('temple_id')->constrained('temples')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();
            $table->string('license_info')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['temple_id', 'sort_order']);
            $table->index(['temple_id', 'is_cover']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temple_media');
    }
};

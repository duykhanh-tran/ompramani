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
        Schema::create('source_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('original_filename');
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 500);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 64);
            $table->string('source_name')->nullable();
            $table->string('translator')->nullable();
            $table->string('publisher')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_documents');
    }
};

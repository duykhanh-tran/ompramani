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
        Schema::create('document_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_document_id')->constrained('source_documents')->cascadeOnDelete();
            $table->foreignId('sutra_id')->nullable()->constrained('sutras')->nullOnDelete();
            $table->foreignId('sutra_version_id')->nullable()->constrained('sutra_versions')->nullOnDelete();

            $table->string('version_name')->nullable();
            $table->string('translator')->nullable();
            $table->string('language', 50)->default('vi');
            $table->string('publisher')->nullable();
            $table->text('source')->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 30)->default('uploaded');
            $table->unsignedInteger('total_sections')->default(0);
            $table->unsignedInteger('processed_sections')->default(0);
            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['status', 'sutra_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_imports');
    }
};

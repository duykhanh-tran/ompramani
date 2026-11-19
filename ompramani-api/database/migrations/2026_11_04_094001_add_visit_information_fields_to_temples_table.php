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
        Schema::table('temples', function (Blueprint $table): void {
            $table->text('visit_notes')->nullable()->after('public_access_status');
            $table->foreignId('visit_notes_source_id')->nullable()->after('visit_notes')->constrained('temple_sources')->nullOnDelete();
            $table->timestamp('visit_notes_verified_at')->nullable()->after('visit_notes_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temples', function (Blueprint $table): void {
            $table->dropForeign(['visit_notes_source_id']);
            $table->dropColumn(['visit_notes', 'visit_notes_source_id', 'visit_notes_verified_at']);
        });
    }
};

<?php

declare(strict_types=1);

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
        if (! Schema::hasTable('prayer_intention_reports')) {
            Schema::create('prayer_intention_reports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('prayer_intention_id')->constrained('prayer_intentions')->cascadeOnDelete();
                $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 50);
                $table->text('details')->nullable();
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_resolution_notes')->nullable();
                $table->timestamps();

                $table->unique(['prayer_intention_id', 'reporter_user_id'], 'uniq_intention_reporter_report');
                $table->index(['status', 'created_at'], 'idx_intention_reports_status_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_intention_reports');
    }
};

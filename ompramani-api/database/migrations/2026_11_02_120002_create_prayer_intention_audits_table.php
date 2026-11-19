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
        Schema::create('prayer_intention_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prayer_intention_id')->constrained('prayer_intentions')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('from_visibility', 20)->nullable();
            $table->string('to_visibility', 20)->nullable();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->string('reason_code', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['prayer_intention_id', 'created_at'], 'idx_prayer_audit_timeline');
            $table->index(['actor_id', 'action'], 'idx_prayer_audit_actor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_intention_audits');
    }
};

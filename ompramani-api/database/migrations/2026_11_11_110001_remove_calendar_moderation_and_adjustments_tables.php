<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely drops calendar adjustment, exception and review override tables.
     */
    public function up(): void
    {
        Schema::dropIfExists('calendar_event_exceptions');
        Schema::dropIfExists('almanac_overrides');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('calendar_event_exceptions')) {
            Schema::create('calendar_event_exceptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();
                $table->unsignedSmallInteger('solar_year');
                $table->string('action', 32);
                $table->date('replacement_solar_date')->nullable();
                $table->string('reason')->nullable();
                $table->string('source_reference')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['calendar_event_id', 'solar_year', 'is_approved']);
            });
        }

        if (! Schema::hasTable('almanac_overrides')) {
            Schema::create('almanac_overrides', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->date('solar_date');
                $table->string('method_key');
                $table->string('day_classification')->nullable();
                $table->string('deity_name')->nullable();
                $table->json('hour_overrides')->nullable();
                $table->text('reason');
                $table->string('source_reference');
                $table->string('status')->default('draft');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['solar_date', 'method_key']);
                $table->index(['solar_date', 'status']);
            });
        }
    }
};

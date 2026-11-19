<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('event_type', 64)->default('buddhist_holiday');
            $table->string('calendar_system', 32)->default('lunar');
            $table->string('recurrence_type', 64)->default('annual_lunar');

            // Solar recurrence fields
            $table->unsignedTinyInteger('solar_month')->nullable();
            $table->unsignedTinyInteger('solar_day')->nullable();
            $table->date('specific_solar_date')->nullable();
            $table->string('solar_feb29_policy', 32)->default('skip');

            // Lunar recurrence fields
            $table->unsignedTinyInteger('lunar_month')->nullable();
            $table->unsignedTinyInteger('lunar_day')->nullable();
            $table->json('lunar_days_json')->nullable();
            $table->unsignedSmallInteger('specific_lunar_year')->nullable();
            $table->boolean('lunar_is_leap_month')->default(false);
            $table->string('lunar_leap_policy', 32)->default('regular_only');
            $table->string('missing_day_policy', 32)->default('skip');

            // Applicability & Display
            $table->boolean('is_general')->default(true);
            $table->integer('sort_order')->default(0);

            // Editorial & Provenance
            $table->string('status', 32)->default('draft');
            $table->string('source_title')->nullable();
            $table->string('source_url', 1024)->nullable();
            $table->text('source_note')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'published_at']);
            $table->index('event_type');
            $table->index('recurrence_type');
            $table->index('calendar_system');
        });

        Schema::create('calendar_event_traditions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->foreignId('tradition_id')->constrained('traditions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['calendar_event_id', 'tradition_id']);
        });

        Schema::create('calendar_event_buddhist_figures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->foreignId('buddhist_figure_id')->constrained('buddhist_figures')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['calendar_event_id', 'buddhist_figure_id']);
        });

        Schema::create('calendar_event_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->unsignedSmallInteger('solar_year');
            $table->string('action', 32); // skip, move
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

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_exceptions');
        Schema::dropIfExists('calendar_event_buddhist_figures');
        Schema::dropIfExists('calendar_event_traditions');
        Schema::dropIfExists('calendar_events');
    }
};

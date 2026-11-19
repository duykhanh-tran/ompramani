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
        Schema::create('almanac_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('version');
            $table->string('source_title');
            $table->string('source_url')->nullable();
            $table->string('license_details')->nullable();
            $table->string('calendar_timezone')->default('Asia/Ho_Chi_Minh');
            $table->date('supported_from');
            $table->date('supported_to');
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->index(['status', 'is_primary']);
        });

        Schema::create('almanac_overrides', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('solar_date');
            $table->string('method_key');
            $table->string('day_classification')->nullable(); // hoang_dao, hac_dao, neutral
            $table->string('deity_name')->nullable();
            $table->json('hour_overrides')->nullable();
            $table->text('reason');
            $table->string('source_reference');
            $table->string('status')->default('draft'); // draft, in_review, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['solar_date', 'method_key']);
            $table->index(['solar_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almanac_overrides');
        Schema::dropIfExists('almanac_methods');
    }
};

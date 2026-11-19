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
        Schema::create('temples', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->json('alternative_names')->nullable();
            $table->string('place_type')->index();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('address_line')->nullable();
            $table->string('ward_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('province_name')->nullable()->index();
            $table->string('country_code', 2)->default('VN')->index();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('coordinate_accuracy')->nullable();
            $table->string('public_access_status')->default('unknown');
            $table->string('public_phone', 50)->nullable();
            $table->string('public_email')->nullable();
            $table->string('official_website', 500)->nullable();
            $table->string('verification_status')->default('unverified')->index();
            $table->string('publication_status')->default('draft')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_code', 'province_name']);
            $table->index(['publication_status', 'published_at']);
            $table->index(['place_type', 'publication_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temples');
    }
};

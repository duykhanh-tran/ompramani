<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplifies buddhist_videos schema constraints: makes category nullable with 'other' default.
     */
    public function up(): void
    {
        Schema::table('buddhist_videos', function (Blueprint $table): void {
            $table->string('category')->nullable()->default('other')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buddhist_videos', function (Blueprint $table): void {
            $table->string('category')->nullable(false)->default(null)->change();
        });
    }
};

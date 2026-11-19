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
            $table->string('cover_image')->nullable()->after('description');
            $table->string('construction_year', 100)->nullable()->after('cover_image');
            $table->string('opening_hours', 255)->nullable()->after('construction_year');
            $table->unsignedInteger('admission_fee')->nullable()->after('opening_hours');
            $table->text('highlights')->nullable()->after('admission_fee');
            $table->longText('history')->nullable()->after('highlights');
            $table->longText('architecture_culture')->nullable()->after('history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temples', function (Blueprint $table): void {
            $table->dropColumn([
                'cover_image',
                'construction_year',
                'opening_hours',
                'admission_fee',
                'highlights',
                'history',
                'architecture_culture',
            ]);
        });
    }
};

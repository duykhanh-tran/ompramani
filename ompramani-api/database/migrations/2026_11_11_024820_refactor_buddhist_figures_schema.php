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
        // 1. Thêm cột vows_introduction vào buddhist_figures
        if (Schema::hasTable('buddhist_figures') && ! Schema::hasColumn('buddhist_figures', 'vows_introduction')) {
            Schema::table('buddhist_figures', function (Blueprint $table): void {
                $table->text('vows_introduction')->nullable()->after('biography');
            });
        }

        // 2. Tạo bảng buddhist_figure_vows lưu từng đại nguyện có cấu trúc
        if (! Schema::hasTable('buddhist_figure_vows')) {
            Schema::create('buddhist_figure_vows', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('buddhist_figure_id')
                    ->constrained('buddhist_figures')
                    ->cascadeOnDelete();
                $table->string('title');
                $table->text('content');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['buddhist_figure_id', 'sort_order']);
            });
        }

        // 3. Tạo bảng trung gian buddhist_figure_sutra cho quan hệ Kinh liên quan
        if (! Schema::hasTable('buddhist_figure_sutra')) {
            Schema::create('buddhist_figure_sutra', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('buddhist_figure_id')
                    ->constrained('buddhist_figures')
                    ->cascadeOnDelete();
                $table->foreignId('sutra_id')
                    ->constrained('sutras')
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['buddhist_figure_id', 'sutra_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddhist_figure_sutra');
        Schema::dropIfExists('buddhist_figure_vows');

        if (Schema::hasTable('buddhist_figures') && Schema::hasColumn('buddhist_figures', 'vows_introduction')) {
            Schema::table('buddhist_figures', function (Blueprint $table): void {
                $table->dropColumn('vows_introduction');
            });
        }
    }
};

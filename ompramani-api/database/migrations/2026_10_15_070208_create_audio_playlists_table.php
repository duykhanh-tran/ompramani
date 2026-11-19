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
        Schema::create('audio_playlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('visibility')->default('private'); // 'public', 'private'
            $table->string('status')->default('draft'); // 'draft', 'published', 'archived'
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['visibility', 'status', 'sort_order']);
            $table->index(['owner_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_playlists');
    }
};

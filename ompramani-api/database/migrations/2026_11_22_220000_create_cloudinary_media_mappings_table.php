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
        Schema::create('cloudinary_media_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('local_path')->unique()->index();
            $table->string('public_id')->index();
            $table->string('resource_type', 32)->default('image')->index();
            $table->string('format', 32)->nullable();
            $table->text('secure_url');
            $table->string('checksum', 64)->nullable()->index();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 32)->default('synced')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudinary_media_mappings');
    }
};

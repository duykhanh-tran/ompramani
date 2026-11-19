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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('notes');
            $table->string('cancellation_reason', 255)->nullable()->after('internal_notes');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');
            $table->timestamp('paid_at')->nullable()->after('completed_at');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('actor_type', 20)->default('system'); // customer, admin, system
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'internal_notes',
                'cancellation_reason',
                'cancelled_at',
                'completed_at',
                'paid_at',
            ]);
        });
    }
};

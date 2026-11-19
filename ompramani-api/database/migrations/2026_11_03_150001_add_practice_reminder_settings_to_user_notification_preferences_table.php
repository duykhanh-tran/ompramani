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
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->boolean('practice_reminders_enabled')->default(false)->after('participation_digest_enabled');
            $table->boolean('quiet_hours_enabled')->default(false)->after('practice_reminders_enabled');
            $table->string('quiet_start_local_time', 5)->nullable()->default('22:00')->after('quiet_hours_enabled');
            $table->string('quiet_end_local_time', 5)->nullable()->default('06:00')->after('quiet_start_local_time');
            $table->string('quiet_hours_timezone', 64)->nullable()->default('Asia/Ho_Chi_Minh')->after('quiet_end_local_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'practice_reminders_enabled',
                'quiet_hours_enabled',
                'quiet_start_local_time',
                'quiet_end_local_time',
                'quiet_hours_timezone',
            ]);
        });
    }
};

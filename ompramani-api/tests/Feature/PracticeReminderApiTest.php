<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PracticeReminderDeliveryStatus;
use App\Enums\PracticeReminderScheduleType;
use App\Enums\PracticeType;
use App\Models\PracticeReminder;
use App\Models\User;
use App\Services\Practice\PracticeReminderDispatchService;
use App\Services\Practice\PracticeReminderScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeReminderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected PracticeReminderScheduleService $scheduleService;

    protected PracticeReminderDispatchService $dispatchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();

        $this->scheduleService = app(PracticeReminderScheduleService::class);
        $this->dispatchService = app(PracticeReminderDispatchService::class);
    }

    // ==========================================
    // 1. CRUD & BASIC LIFECYCLE TESTS
    // ==========================================

    public function test_user_can_create_daily_practice_reminder(): void
    {
        $res = $this->actingAs($this->userA)->postJson('/api/v1/me/practice-reminders', [
            'practice_type' => PracticeType::Meditation->value,
            'title' => 'Thiền buổi sáng',
            'schedule_type' => PracticeReminderScheduleType::Daily->value,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        $res->assertCreated()
            ->assertJson([
                'data' => [
                    'practice_type' => 'meditation',
                    'practice_label' => 'Thiền Định',
                    'title' => 'Thiền buổi sáng',
                    'schedule_type' => 'daily',
                    'local_time' => '06:00',
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'is_enabled' => true,
                    'destination_url' => '/thien',
                ],
            ]);

        $this->assertDatabaseHas('practice_reminders', [
            'user_id' => $this->userA->id,
            'practice_type' => 'meditation',
            'local_time' => '06:00',
            'is_enabled' => true,
        ]);

        $reminder = PracticeReminder::where('user_id', $this->userA->id)->first();
        $this->assertNotNull($reminder->next_due_at_utc);
    }

    public function test_user_can_create_weekly_practice_reminder(): void
    {
        $res = $this->actingAs($this->userA)->postJson('/api/v1/me/practice-reminders', [
            'practice_type' => PracticeType::SutraReading->value,
            'title' => 'Đọc Kinh tối',
            'schedule_type' => PracticeReminderScheduleType::Weekly->value,
            'local_time' => '20:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'weekdays' => [1, 3, 5], // Mon, Wed, Fri
            'is_enabled' => true,
        ]);

        $res->assertCreated()
            ->assertJson([
                'data' => [
                    'practice_type' => 'sutra_reading',
                    'practice_label' => 'Đọc Kinh',
                    'schedule_type' => 'weekly',
                    'weekdays' => [1, 3, 5],
                    'destination_url' => '/kinh',
                ],
            ]);
    }

    public function test_user_can_create_one_time_future_practice_reminder(): void
    {
        $futureDate = now()->addDays(5)->format('Y-m-d');

        $res = $this->actingAs($this->userA)->postJson('/api/v1/me/practice-reminders', [
            'practice_type' => PracticeType::Mala->value,
            'title' => 'Tràng hạt đặc biệt',
            'schedule_type' => PracticeReminderScheduleType::Once->value,
            'local_date' => $futureDate,
            'local_time' => '10:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        $res->assertCreated()
            ->assertJson([
                'data' => [
                    'practice_type' => 'mala',
                    'schedule_type' => 'once',
                    'local_date' => $futureDate,
                    'destination_url' => '/trang-hat',
                ],
            ]);
    }

    public function test_one_time_past_date_is_rejected(): void
    {
        $pastDate = now()->subDays(2)->format('Y-m-d');

        $res = $this->actingAs($this->userA)->postJson('/api/v1/me/practice-reminders', [
            'practice_type' => PracticeType::Mala->value,
            'schedule_type' => PracticeReminderScheduleType::Once->value,
            'local_date' => $pastDate,
            'local_time' => '10:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['local_date']);
    }

    public function test_user_can_list_only_own_reminders(): void
    {
        PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        PracticeReminder::create([
            'user_id' => $this->userB->id,
            'practice_type' => PracticeType::Mala,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '07:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        $resA = $this->actingAs($this->userA)->getJson('/api/v1/me/practice-reminders');
        $resA->assertOk();
        $this->assertCount(1, $resA->json('data'));
        $this->assertSame('meditation', $resA->json('data.0.practice_type'));

        $resB = $this->actingAs($this->userB)->getJson('/api/v1/me/practice-reminders');
        $resB->assertOk();
        $this->assertCount(1, $resB->json('data'));
        $this->assertSame('mala', $resB->json('data.0.practice_type'));
    }

    public function test_user_can_update_own_reminder(): void
    {
        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'schedule_revision' => 1,
        ]);

        $res = $this->actingAs($this->userA)->patchJson("/api/v1/me/practice-reminders/{$reminder->id}", [
            'local_time' => '07:30',
            'title' => 'Thiền sớm mỗi sáng',
        ]);

        $res->assertOk()
            ->assertJson([
                'data' => [
                    'local_time' => '07:30',
                    'title' => 'Thiền sớm mỗi sáng',
                    'schedule_revision' => 2,
                ],
            ]);

        $this->assertSame(2, $reminder->fresh()->schedule_revision);
    }

    public function test_user_can_pause_and_resume_reminder(): void
    {
        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'next_due_at_utc' => now()->addHours(2),
        ]);

        // Pause
        $pauseRes = $this->actingAs($this->userA)->postJson("/api/v1/me/practice-reminders/{$reminder->id}/pause");
        $pauseRes->assertOk()->assertJson(['data' => ['is_enabled' => false, 'next_due_at' => null]]);
        $this->assertFalse($reminder->fresh()->is_enabled);
        $this->assertNull($reminder->fresh()->next_due_at_utc);

        // Resume
        $resumeRes = $this->actingAs($this->userA)->postJson("/api/v1/me/practice-reminders/{$reminder->id}/resume");
        $resumeRes->assertOk()->assertJson(['data' => ['is_enabled' => true]]);
        $this->assertTrue($reminder->fresh()->is_enabled);
        $this->assertNotNull($reminder->fresh()->next_due_at_utc);
    }

    public function test_user_can_delete_own_reminder(): void
    {
        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        $res = $this->actingAs($this->userA)->deleteJson("/api/v1/me/practice-reminders/{$reminder->id}");
        $res->assertOk();

        $this->assertSoftDeleted('practice_reminders', ['id' => $reminder->id]);
    }

    // ==========================================
    // 2. AUTHORIZATION & SECURITY TESTS
    // ==========================================

    public function test_guest_cannot_access_practice_reminders(): void
    {
        $this->getJson('/api/v1/me/practice-reminders')->assertUnauthorized();
        $this->postJson('/api/v1/me/practice-reminders', [])->assertUnauthorized();
        $this->getJson('/api/v1/me/practice-reminder-settings')->assertUnauthorized();
    }

    public function test_user_a_cannot_access_user_b_reminder(): void
    {
        $reminderB = PracticeReminder::create([
            'user_id' => $this->userB->id,
            'practice_type' => PracticeType::Mala,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '07:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        // User A tries to view
        $this->actingAs($this->userA)->getJson("/api/v1/me/practice-reminders/{$reminderB->id}")->assertNotFound();

        // User A tries to edit
        $this->actingAs($this->userA)->patchJson("/api/v1/me/practice-reminders/{$reminderB->id}", [
            'local_time' => '08:00',
        ])->assertNotFound();

        // User A tries to delete
        $this->actingAs($this->userA)->deleteJson("/api/v1/me/practice-reminders/{$reminderB->id}")->assertNotFound();

        // User A tries to pause
        $this->actingAs($this->userA)->postJson("/api/v1/me/practice-reminders/{$reminderB->id}/pause")->assertNotFound();
    }

    public function test_max_active_reminder_limit_enforced(): void
    {
        // Create 20 active reminders for User A
        for ($i = 0; $i < 20; $i++) {
            PracticeReminder::create([
                'user_id' => $this->userA->id,
                'practice_type' => PracticeType::Meditation,
                'schedule_type' => PracticeReminderScheduleType::Daily,
                'local_time' => sprintf('%02d:00', $i),
                'timezone' => 'Asia/Ho_Chi_Minh',
                'is_enabled' => true,
            ]);
        }

        // 21st active reminder should be rejected
        $res = $this->actingAs($this->userA)->postJson('/api/v1/me/practice-reminders', [
            'practice_type' => PracticeType::Mala->value,
            'schedule_type' => PracticeReminderScheduleType::Daily->value,
            'local_time' => '21:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
        ]);

        $res->assertStatus(422)
            ->assertJsonFragment(['message' => 'Bạn đã đạt giới hạn tối đa 20 lời nhắc tu tập đang kích hoạt.']);
    }

    // ==========================================
    // 3. SETTINGS & QUIET HOURS TESTS
    // ==========================================

    public function test_user_can_view_and_update_practice_reminder_settings(): void
    {
        $getRes = $this->actingAs($this->userA)->getJson('/api/v1/me/practice-reminder-settings');
        $getRes->assertOk()
            ->assertJson([
                'data' => [
                    'practice_reminders_enabled' => false,
                    'quiet_hours_enabled' => false,
                ],
            ]);

        $putRes = $this->actingAs($this->userA)->putJson('/api/v1/me/practice-reminder-settings', [
            'practice_reminders_enabled' => true,
            'quiet_hours_enabled' => true,
            'quiet_start_local_time' => '22:30',
            'quiet_end_local_time' => '05:30',
            'quiet_hours_timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $putRes->assertOk()
            ->assertJson([
                'data' => [
                    'practice_reminders_enabled' => true,
                    'quiet_hours_enabled' => true,
                    'quiet_start_local_time' => '22:30',
                    'quiet_end_local_time' => '05:30',
                    'quiet_hours_timezone' => 'Asia/Ho_Chi_Minh',
                ],
            ]);
    }

    public function test_quiet_hours_cross_midnight_evaluation(): void
    {
        $pref = $this->userA->getEffectiveNotificationPreferences();
        $pref->update([
            'quiet_hours_enabled' => true,
            'quiet_start_local_time' => '22:00',
            'quiet_end_local_time' => '06:00',
            'quiet_hours_timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        // 23:00 in Vietnam is in quiet hours (22:00 -> 06:00)
        $time23 = Carbon::parse('2026-11-20 23:00:00', 'Asia/Ho_Chi_Minh')->utc();
        $this->assertTrue($this->scheduleService->isQuietHours($time23, $pref));

        // 05:30 in Vietnam is in quiet hours
        $time0530 = Carbon::parse('2026-11-20 05:30:00', 'Asia/Ho_Chi_Minh')->utc();
        $this->assertTrue($this->scheduleService->isQuietHours($time0530, $pref));

        // 06:00 is boundary (exclusive end) -> outside quiet hours
        $time0600 = Carbon::parse('2026-11-20 06:00:00', 'Asia/Ho_Chi_Minh')->utc();
        $this->assertFalse($this->scheduleService->isQuietHours($time0600, $pref));

        // 14:00 is afternoon -> outside quiet hours
        $time1400 = Carbon::parse('2026-11-20 14:00:00', 'Asia/Ho_Chi_Minh')->utc();
        $this->assertFalse($this->scheduleService->isQuietHours($time1400, $pref));
    }

    public function test_quiet_hours_same_day_evaluation(): void
    {
        $pref = $this->userA->getEffectiveNotificationPreferences();
        $pref->update([
            'quiet_hours_enabled' => true,
            'quiet_start_local_time' => '13:00',
            'quiet_end_local_time' => '14:30',
            'quiet_hours_timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $time1330 = Carbon::parse('2026-11-20 13:30:00', 'Asia/Ho_Chi_Minh')->utc();
        $this->assertTrue($this->scheduleService->isQuietHours($time1330, $pref));

        $time1500 = Carbon::parse('2026-11-20 15:00:00', 'Asia/Ho_Chi_Minh')->utc();
        $this->assertFalse($this->scheduleService->isQuietHours($time1500, $pref));
    }

    // ==========================================
    // 4. DISPATCH ENGINE & IDEMPOTENCY TESTS
    // ==========================================

    public function test_due_reminder_dispatches_in_app_notification(): void
    {
        // 1. Enable practice reminders for User A
        $this->userA->getEffectiveNotificationPreferences()->update([
            'practice_reminders_enabled' => true,
            'quiet_hours_enabled' => false,
        ]);

        // 2. Create due reminder
        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'title' => 'Thiền định hằng ngày',
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'schedule_revision' => 1,
            'next_due_at_utc' => now()->subMinute(), // Due now
        ]);

        // Run dispatch service
        $stats = $this->dispatchService->dispatchDueReminders();
        $this->assertSame(1, $stats['sent']);

        // Check delivery ledger
        $this->assertDatabaseHas('practice_reminder_deliveries', [
            'practice_reminder_id' => $reminder->id,
            'user_id' => $this->userA->id,
            'status' => PracticeReminderDeliveryStatus::Sent->value,
        ]);

        // Check in-app notification in Task 35 inbox
        $notification = $this->userA->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('personal_practice_reminder', $notification->data['type']);
        $this->assertSame('practice_reminder', $notification->data['category']);
        $this->assertSame('/thien', $notification->data['destination']);
        $this->assertStringContainsString('thiền định', $notification->data['message']);

        // Verify reminder next_due advanced into the future
        $this->assertTrue($reminder->fresh()->next_due_at_utc->greaterThan(now()));
    }

    public function test_reminder_skipped_when_global_preference_is_disabled(): void
    {
        // Global preference is false by default
        $this->userA->getEffectiveNotificationPreferences()->update([
            'practice_reminders_enabled' => false,
        ]);

        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'next_due_at_utc' => now()->subMinute(),
        ]);

        $stats = $this->dispatchService->dispatchDueReminders();
        $this->assertSame(1, $stats['skipped_disabled']);

        // No notification delivered
        $this->assertSame(0, $this->userA->notifications()->count());

        // Delivery ledger marked as skipped_disabled
        $this->assertDatabaseHas('practice_reminder_deliveries', [
            'practice_reminder_id' => $reminder->id,
            'status' => PracticeReminderDeliveryStatus::SkippedDisabled->value,
        ]);
    }

    public function test_reminder_skipped_during_quiet_hours(): void
    {
        $nowLocal = Carbon::now('Asia/Ho_Chi_Minh');
        $quietStart = $nowLocal->copy()->subHours(2)->format('H:i');
        $quietEnd = $nowLocal->copy()->addHours(2)->format('H:i');

        $this->userA->getEffectiveNotificationPreferences()->update([
            'practice_reminders_enabled' => true,
            'quiet_hours_enabled' => true,
            'quiet_start_local_time' => $quietStart,
            'quiet_end_local_time' => $quietEnd,
            'quiet_hours_timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $scheduledTime = now()->subMinute();

        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::BellWoodenFish,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => $nowLocal->format('H:i'),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'next_due_at_utc' => $scheduledTime,
        ]);

        $result = $this->dispatchService->processReminder($reminder);
        $this->assertSame('skipped_quiet_hours', $result);

        // No notification sent
        $this->assertSame(0, $this->userA->notifications()->count());

        $this->assertDatabaseHas('practice_reminder_deliveries', [
            'practice_reminder_id' => $reminder->id,
            'status' => PracticeReminderDeliveryStatus::SkippedQuietHours->value,
        ]);
    }

    public function test_missed_reminder_beyond_grace_window_is_skipped(): void
    {
        $this->userA->getEffectiveNotificationPreferences()->update([
            'practice_reminders_enabled' => true,
            'quiet_hours_enabled' => false,
        ]);

        // Reminder was due 3 hours ago (server was down)
        $threeHoursAgo = now()->subHours(3);

        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'next_due_at_utc' => $threeHoursAgo,
        ]);

        $result = $this->dispatchService->processReminder($reminder);
        $this->assertSame('skipped_late', $result);

        // Does NOT flood inbox
        $this->assertSame(0, $this->userA->notifications()->count());

        $this->assertDatabaseHas('practice_reminder_deliveries', [
            'practice_reminder_id' => $reminder->id,
            'status' => PracticeReminderDeliveryStatus::SkippedLate->value,
        ]);

        // Advances to next future occurrence without schedule drift
        $this->assertTrue($reminder->fresh()->next_due_at_utc->greaterThan(now()));
    }

    public function test_repeated_dispatch_is_idempotent(): void
    {
        $this->userA->getEffectiveNotificationPreferences()->update([
            'practice_reminders_enabled' => true,
            'quiet_hours_enabled' => false,
        ]);

        $dueInstant = now()->subMinute();

        $reminder = PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'next_due_at_utc' => $dueInstant,
        ]);

        // First run: sent
        $res1 = $this->dispatchService->processReminder($reminder);
        $this->assertSame('sent', $res1);
        $this->assertSame(1, $this->userA->notifications()->count());

        // Repeated run with same due instant: duplicate prevented by unique ledger constraint
        $reminder->refresh();
        $reminder->next_due_at_utc = $dueInstant;
        $reminder->save();

        $res2 = $this->dispatchService->processReminder($reminder);
        $this->assertSame('duplicate', $res2);
        // Notification count remains exactly 1
        $this->assertSame(1, $this->userA->notifications()->count());
    }

    public function test_artisan_dispatch_command_works(): void
    {
        $this->userA->getEffectiveNotificationPreferences()->update([
            'practice_reminders_enabled' => true,
        ]);

        PracticeReminder::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Mala,
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '07:00',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_enabled' => true,
            'next_due_at_utc' => now()->subMinute(),
        ]);

        $this->artisan('practice-reminders:dispatch-due')
            ->expectsOutputToContain('Hoàn tất')
            ->assertSuccessful();
    }

    // ==========================================
    // 5. DST & TIMEZONE RECURRENCE TESTS
    // ==========================================

    public function test_dst_spring_forward_gap_is_skipped_for_daily(): void
    {
        // In America/New_York on 2026-03-08 at 02:00, clock jumps to 03:00.
        // 02:30 is a nonexistent local time.
        $this->assertTrue(
            $this->scheduleService->isNonexistentLocalTime('2026-03-08', '02:30', 'America/New_York')
        );

        $reminder = new PracticeReminder([
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '02:30',
            'timezone' => 'America/New_York',
        ]);

        // Start from 2026-03-07 at 02:30 NY time
        $startUtc = Carbon::parse('2026-03-07 02:30:00', 'America/New_York')->utc();
        $nextDue = $this->scheduleService->calculateNextDue($reminder, $startUtc);

        $this->assertNotNull($nextDue);
        // Because 2026-03-08 02:30 does not exist, policy advances to next day: 2026-03-09 02:30 NY time!
        $nextLocal = $nextDue->copy()->setTimezone('America/New_York');
        $this->assertSame('2026-03-09', $nextLocal->format('Y-m-d'));
        $this->assertSame('02:30', $nextLocal->format('H:i'));
    }

    public function test_daily_recurrence_maintains_wall_clock_time_across_dst(): void
    {
        // 06:00 in America/New_York
        $reminder = new PracticeReminder([
            'schedule_type' => PracticeReminderScheduleType::Daily,
            'local_time' => '06:00',
            'timezone' => 'America/New_York',
        ]);

        // Day before DST change
        $startUtc = Carbon::parse('2026-03-07 06:00:00', 'America/New_York')->utc();
        $nextDue = $this->scheduleService->calculateNextDue($reminder, $startUtc);

        $this->assertNotNull($nextDue);
        // On 2026-03-08, wall clock must still be exactly 06:00 local time
        $nextLocal = $nextDue->copy()->setTimezone('America/New_York');
        $this->assertSame('2026-03-08', $nextLocal->format('Y-m-d'));
        $this->assertSame('06:00', $nextLocal->format('H:i'));
    }
}

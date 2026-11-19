<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PracticePlanScheduleType;
use App\Enums\PracticePlanStatus;
use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticePlan;
use App\Models\PracticePlanItem;
use App\Models\PracticePlanOccurrenceSession;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PracticePlanApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'Phật tử Tuệ Minh',
            'email' => 'tueminh@example.com',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'Phật tử Diệu Hạnh',
            'email' => 'dieuhanh@example.com',
        ]);
    }

    // ==========================================
    // 1. PLAN CRUD & AUTHORIZATION TESTS
    // ==========================================

    public function test_guest_cannot_access_practice_plans(): void
    {
        $this->getJson('/api/v1/me/practice-plans')->assertUnauthorized();
        $this->postJson('/api/v1/me/practice-plans', ['name' => 'Thời khóa sáng'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_practice_plan(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/v1/me/practice-plans', [
            'name' => 'Thời khóa tu tập sáng',
            'description' => 'Tĩnh tâm thiền định và niệm Phật đầu ngày',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Thời khóa tu tập sáng')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.timezone', 'Asia/Ho_Chi_Minh');

        $this->assertDatabaseHas('practice_plans', [
            'user_id' => $this->userA->id,
            'name' => 'Thời khóa tu tập sáng',
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_create_plan_with_invalid_timezone(): void
    {
        Sanctum::actingAs($this->userA);

        $this->postJson('/api/v1/me/practice-plans', [
            'name' => 'Kế hoạch lỗi',
            'timezone' => 'Mars/Olympus_Mons',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['timezone']);
    }

    public function test_user_can_view_own_plan(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Thời khóa chiều',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $this->getJson("/api/v1/me/practice-plans/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $plan->id)
            ->assertJsonPath('data.name', 'Thời khóa chiều');
    }

    public function test_user_cannot_view_or_modify_another_users_plan(): void
    {
        $planB = PracticePlan::create([
            'user_id' => $this->userB->id,
            'name' => 'Kế hoạch của User B',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        Sanctum::actingAs($this->userA);

        // View User B's plan
        $this->getJson("/api/v1/me/practice-plans/{$planB->id}")->assertForbidden();

        // Update User B's plan
        $this->patchJson("/api/v1/me/practice-plans/{$planB->id}", [
            'name' => 'Hacked Plan',
        ])->assertForbidden();

        // Delete User B's plan
        $this->deleteJson("/api/v1/me/practice-plans/{$planB->id}")->assertForbidden();

        // Pause User B's plan
        $this->postJson("/api/v1/me/practice-plans/{$planB->id}/pause")->assertForbidden();
    }

    public function test_user_can_pause_resume_and_archive_plan(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Thời khóa thiền',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        // Pause
        $this->postJson("/api/v1/me/practice-plans/{$plan->id}/pause")
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');
        $this->assertSame(PracticePlanStatus::Paused, $plan->fresh()->status);

        // Resume
        $this->postJson("/api/v1/me/practice-plans/{$plan->id}/resume")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
        $this->assertSame(PracticePlanStatus::Active, $plan->fresh()->status);

        // Archive
        $this->postJson("/api/v1/me/practice-plans/{$plan->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
        $this->assertSame(PracticePlanStatus::Archived, $plan->fresh()->status);
    }

    public function test_user_can_delete_plan(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch xóa',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $this->deleteJson("/api/v1/me/practice-plans/{$plan->id}")
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertSoftDeleted('practice_plans', ['id' => $plan->id]);
    }

    // ==========================================
    // 2. PLAN ITEMS CRUD TESTS
    // ==========================================

    public function test_user_can_add_weekly_and_once_items_to_plan(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch tuần',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        // 1. Weekly Meditation
        $resWeekly = $this->postJson("/api/v1/me/practice-plans/{$plan->id}/items", [
            'practice_type' => 'meditation',
            'title' => 'Thiền định tĩnh tâm',
            'schedule_type' => 'weekly',
            'local_time' => '06:00',
            'weekdays' => [1, 3, 5], // T2, T4, T6
            'target_duration_seconds' => 1200, // 20 mins
        ]);

        $resWeekly->assertCreated()
            ->assertJsonPath('data.practice_type', 'meditation')
            ->assertJsonPath('data.schedule_type', 'weekly')
            ->assertJsonPath('data.weekdays', [1, 3, 5])
            ->assertJsonPath('data.target_duration_seconds', 1200);

        // 2. Once Sutra Reading
        $resOnce = $this->postJson("/api/v1/me/practice-plans/{$plan->id}/items", [
            'practice_type' => 'sutra_reading',
            'title' => 'Đọc Kinh Phổ Môn',
            'schedule_type' => 'once',
            'local_date' => '2026-11-15',
            'local_time' => '19:30',
        ]);

        $resOnce->assertCreated()
            ->assertJsonPath('data.practice_type', 'sutra_reading')
            ->assertJsonPath('data.schedule_type', 'once')
            ->assertJsonPath('data.local_date', '2026-11-15');
    }

    public function test_cannot_add_item_with_invalid_weekdays(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch tuần',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        // Weekday 8 is invalid (only 1..7)
        $this->postJson("/api/v1/me/practice-plans/{$plan->id}/items", [
            'practice_type' => 'meditation',
            'schedule_type' => 'weekly',
            'weekdays' => [1, 8],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['weekdays.1']);

        // Duplicate weekdays
        $this->postJson("/api/v1/me/practice-plans/{$plan->id}/items", [
            'practice_type' => 'meditation',
            'schedule_type' => 'weekly',
            'weekdays' => [1, 1],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['weekdays.0']);
    }

    public function test_updating_item_schedule_bumps_revision(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'local_time' => '06:00',
            'weekdays' => [1, 3, 5],
            'schedule_revision' => 1,
            'is_enabled' => true,
        ]);

        $this->patchJson("/api/v1/me/practice-plan-items/{$item->id}", [
            'local_time' => '07:00',
        ])->assertOk()
            ->assertJsonPath('data.local_time', '07:00')
            ->assertJsonPath('data.schedule_revision', 2);

        $this->assertSame(2, $item->fresh()->schedule_revision);
    }

    public function test_item_can_opt_in_to_create_linked_reminder(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch có nhắc nhở',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $response = $this->postJson("/api/v1/me/practice-plans/{$plan->id}/items", [
            'practice_type' => 'mala',
            'title' => 'Niệm Phật chuỗi hạt',
            'schedule_type' => 'weekly',
            'local_time' => '08:00',
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'create_reminder' => true, // Opt-in
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.has_reminder', true);

        $item = PracticePlanItem::find($response->json('data.id'));
        $this->assertNotNull($item->practice_reminder_id);

        $this->assertDatabaseHas('practice_reminders', [
            'id' => $item->practice_reminder_id,
            'user_id' => $this->userA->id,
            'practice_type' => 'mala',
            'local_time' => '08:00',
        ]);
    }

    // ==========================================
    // 3. OCCURRENCE ENGINE TESTS
    // ==========================================

    public function test_occurrences_generated_on_correct_weekdays(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch thiền tuần',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        // Monday (1) and Friday (5)
        PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'local_time' => '06:00',
            'weekdays' => [1, 5],
            'is_enabled' => true,
        ]);

        // Query week from 2026-11-02 (Monday) to 2026-11-08 (Sunday)
        $res = $this->getJson('/api/v1/me/practice-plan-occurrences?from=2026-11-02&to=2026-11-08');
        $res->assertOk();

        $data = $res->json('data');
        // Must contain exactly 2 occurrences: Monday (2026-11-02) and Friday (2026-11-06)
        $this->assertCount(2, $data);
        $this->assertSame('2026-11-02', $data[0]['local_date']);
        $this->assertSame('2026-11-06', $data[1]['local_date']);
        $this->assertSame('planned', $data[0]['status']);
    }

    public function test_range_limit_exceeding_62_days_is_rejected(): void
    {
        Sanctum::actingAs($this->userA);

        // 105 days query exceeds max 62 days
        $this->getJson('/api/v1/me/practice-plan-occurrences?from=2026-01-01&to=2026-04-15')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_today_and_week_convenience_endpoints_work(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch hằng ngày',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::BellWoodenFish,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'local_time' => '19:00',
            'weekdays' => [1, 2, 3, 4, 5, 6, 7], // Every day
            'is_enabled' => true,
        ]);

        $this->getJson('/api/v1/me/practice-planner/today')
            ->assertOk()
            ->assertJsonStructure(['data', 'today', 'timezone'])
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/me/practice-planner/week')
            ->assertOk()
            ->assertJsonStructure(['data', 'week' => ['from', 'to', 'today'], 'timezone'])
            ->assertJsonCount(7, 'data');
    }

    // ==========================================
    // 4. SKIP & RESTORE TESTS
    // ==========================================

    public function test_user_can_skip_and_restore_occurrence(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch thiền',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'local_time' => '06:00',
            'weekdays' => [1, 3, 5],
            'is_enabled' => true,
        ]);

        // Skip Monday 2026-11-02
        $resSkip = $this->postJson('/api/v1/me/practice-plan-occurrences/skip', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
            'reason' => 'Bận công việc Phật sự buổi sáng',
        ]);
        $resSkip->assertOk();

        $this->assertDatabaseHas('practice_plan_occurrence_overrides', [
            'practice_plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
            'action' => 'skip',
        ]);

        // Check occurrence engine: Monday 2026-11-02 should be 'skipped', Friday 2026-11-06 remains 'planned'
        $resOcc = $this->getJson('/api/v1/me/practice-plan-occurrences?from=2026-11-02&to=2026-11-08');
        $data = $resOcc->json('data');
        $this->assertSame('skipped', $data[0]['status']);
        $this->assertTrue($data[0]['can_restore']);
        $this->assertFalse($data[0]['can_skip']);
        $this->assertSame('planned', $data[1]['status']);

        // Restore Monday 2026-11-02
        $resRestore = $this->postJson('/api/v1/me/practice-plan-occurrences/restore', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
        ]);
        $resRestore->assertOk();

        // Check occurrence engine: Monday is back to 'planned'
        $resOccAfter = $this->getJson('/api/v1/me/practice-plan-occurrences?from=2026-11-02&to=2026-11-08');
        $this->assertSame('planned', $resOccAfter->json('data.0.status'));
    }

    public function test_user_cannot_skip_another_users_occurrence(): void
    {
        $planB = PracticePlan::create([
            'user_id' => $this->userB->id,
            'name' => 'Kế hoạch B',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $itemB = PracticePlanItem::create([
            'practice_plan_id' => $planB->id,
            'user_id' => $this->userB->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'local_time' => '06:00',
            'weekdays' => [1],
            'is_enabled' => true,
        ]);

        Sanctum::actingAs($this->userA);

        $this->postJson('/api/v1/me/practice-plan-occurrences/skip', [
            'plan_item_id' => $itemB->id,
            'local_date' => '2026-11-02',
        ])->assertUnprocessable();
    }

    // ==========================================
    // 5. PRACTICE SESSION LINKING TESTS
    // ==========================================

    public function test_user_can_link_completed_session_to_occurrence(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch thiền',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'local_time' => '06:00',
            'weekdays' => [1], // Monday
            'target_duration_seconds' => 1200, // 20 mins planned target
            'is_enabled' => true,
        ]);

        // User completed a real 15-minute meditation session on Monday 2026-11-02 at 06:15 local time (23:15 UTC previous day)
        $endedAtUtc = Carbon::parse('2026-11-02 06:15:00', 'Asia/Ho_Chi_Minh')->utc();
        $startedAtUtc = $endedAtUtc->copy()->subMinutes(15);

        $session = PracticeSession::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'started_at' => $startedAtUtc,
            'ended_at' => $endedAtUtc,
            'active_duration_seconds' => 900, // 15 mins actual
            'sequence' => 1,
        ]);

        // Link session
        $resLink = $this->postJson('/api/v1/me/practice-plan-occurrences/link-session', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
            'practice_session_id' => $session->id,
        ]);
        $resLink->assertOk();

        $this->assertDatabaseHas('practice_plan_occurrence_sessions', [
            'practice_plan_item_id' => $item->id,
            'occurrence_local_date' => '2026-11-02',
            'practice_session_id' => $session->id,
        ]);

        // Verify occurrence status in query: Practiced with authoritative actual metrics
        $resOcc = $this->getJson('/api/v1/me/practice-plan-occurrences?from=2026-11-02&to=2026-11-08');
        $occ = $resOcc->json('data.0');

        $this->assertSame('practiced', $occ['status']);
        $this->assertSame(1200, $occ['target']['duration_seconds']); // Target intact
        $this->assertSame(900, $occ['actual']['duration_seconds']); // Actual from session
        $this->assertSame($session->id, $occ['linked_session_id']);
        $this->assertFalse($occ['can_start']);
        $this->assertFalse($occ['can_skip']);
    }

    public function test_cannot_link_active_or_cancelled_session(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'weekdays' => [1],
            'is_enabled' => true,
        ]);

        // Active session
        $activeSession = PracticeSession::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'started_at' => now(),
            'active_duration_seconds' => 100,
            'sequence' => 1,
        ]);

        $this->postJson('/api/v1/me/practice-plan-occurrences/link-session', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
            'practice_session_id' => $activeSession->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['practice_session_id']);
    }

    public function test_cannot_link_another_users_session(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'weekdays' => [1],
            'is_enabled' => true,
        ]);

        $sessionOfUserB = PracticeSession::create([
            'user_id' => $this->userB->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'active_duration_seconds' => 600,
            'sequence' => 1,
        ]);

        $this->postJson('/api/v1/me/practice-plan-occurrences/link-session', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
            'practice_session_id' => $sessionOfUserB->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['practice_session_id']);
    }

    public function test_cannot_link_session_with_practice_type_mismatch(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        // Item is Meditation
        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'weekdays' => [1],
            'is_enabled' => true,
        ]);

        // Session is Mala (Tràng hạt)
        $session = PracticeSession::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'started_at' => Carbon::parse('2026-11-02 06:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-11-02 06:20:00', 'Asia/Ho_Chi_Minh')->utc(),
            'active_duration_seconds' => 1200,
            'sequence' => 1,
        ]);

        $this->postJson('/api/v1/me/practice-plan-occurrences/link-session', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
            'practice_session_id' => $session->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['practice_session_id']);
    }

    public function test_unlinking_session_does_not_delete_practice_session(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'weekdays' => [1],
            'is_enabled' => true,
        ]);

        $endedAt = Carbon::parse('2026-11-02 06:15:00', 'Asia/Ho_Chi_Minh')->utc();
        $session = PracticeSession::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'started_at' => $endedAt->copy()->subMinutes(15),
            'ended_at' => $endedAt,
            'active_duration_seconds' => 900,
            'sequence' => 1,
        ]);

        PracticePlanOccurrenceSession::create([
            'practice_plan_item_id' => $item->id,
            'user_id' => $this->userA->id,
            'occurrence_local_date' => '2026-11-02',
            'schedule_revision' => 1,
            'practice_session_id' => $session->id,
            'linked_at' => now(),
        ]);

        // Unlink
        $res = $this->deleteJson('/api/v1/me/practice-plan-occurrences/unlink-session', [
            'plan_item_id' => $item->id,
            'local_date' => '2026-11-02',
        ]);
        $res->assertOk();

        // Link record deleted
        $this->assertDatabaseMissing('practice_plan_occurrence_sessions', [
            'practice_plan_item_id' => $item->id,
            'occurrence_local_date' => '2026-11-02',
        ]);

        // PracticeSession STILL EXISTS in the database!
        $this->assertDatabaseHas('practice_sessions', [
            'id' => $session->id,
            'status' => PracticeSessionStatus::Completed->value,
        ]);
    }

    public function test_candidate_sessions_endpoint_returns_unlinked_completed_sessions(): void
    {
        Sanctum::actingAs($this->userA);

        $plan = PracticePlan::create([
            'user_id' => $this->userA->id,
            'name' => 'Kế hoạch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => PracticePlanStatus::Active,
        ]);

        $item = PracticePlanItem::create([
            'practice_plan_id' => $plan->id,
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'schedule_type' => PracticePlanScheduleType::Weekly,
            'weekdays' => [1],
            'is_enabled' => true,
        ]);

        $endedAt = Carbon::parse('2026-11-02 06:15:00', 'Asia/Ho_Chi_Minh')->utc();
        $session = PracticeSession::create([
            'user_id' => $this->userA->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'started_at' => $endedAt->copy()->subMinutes(15),
            'ended_at' => $endedAt,
            'active_duration_seconds' => 900,
            'sequence' => 1,
        ]);

        $res = $this->getJson("/api/v1/me/practice-plan-occurrences/candidate-sessions?plan_item_id={$item->id}&local_date=2026-11-02");
        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $session->id);
    }
}

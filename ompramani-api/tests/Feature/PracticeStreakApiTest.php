<?php

namespace Tests\Feature;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PracticeStreakApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create([
            'name' => 'Phật tử Tuệ An',
            'email' => 'tuean@example.com',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Phật tử Diệu Hạnh',
            'email' => 'dieuhanh@example.com',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_practice_streak(): void
    {
        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(401);
    }

    public function test_new_user_with_no_sessions_returns_zero_streak(): void
    {
        Sanctum::actingAs($this->user1);

        $now = Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        $res = $this->getJson('/api/v1/me/practice-streak?days=7')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'local_today' => '2026-10-15',
                    'current_streak_days' => 0,
                    'longest_streak_days' => 0,
                    'total_qualifying_days' => 0,
                    'last_qualified_date' => null,
                    'has_qualified_today' => false,
                    'awaiting_today' => false,
                    'status' => 'inactive',
                ],
            ]);

        $calendar = $res->json('data.calendar_days');
        $this->assertCount(7, $calendar);
        $this->assertFalse($calendar[6]['qualified']);
        $this->assertTrue($calendar[6]['is_today']);
        $this->assertEquals('2026-10-15', $calendar[6]['date']);
    }

    public function test_one_qualifying_session_today_gives_current_streak_one(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 14:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900,
            'started_at' => $now->copy()->subMinutes(15)->utc(),
            'ended_at' => $now->copy()->utc(),
        ]);

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 1,
                    'longest_streak_days' => 1,
                    'total_qualifying_days' => 1,
                    'last_qualified_date' => '2026-10-15',
                    'has_qualified_today' => true,
                    'awaiting_today' => false,
                    'status' => 'active_today',
                ],
            ]);
    }

    public function test_two_qualifying_sessions_same_day_counts_as_one_day(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 18:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Session 1: morning meditation
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => Carbon::parse('2026-10-15 06:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-15 06:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Session 2: afternoon mala
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 700,
            'progress_data' => ['bead_count' => 108],
            'started_at' => Carbon::parse('2026-10-15 14:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-15 14:15:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        $res = $this->getJson('/api/v1/me/practice-streak?days=7')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 1,
                    'longest_streak_days' => 1,
                    'total_qualifying_days' => 1,
                    'has_qualified_today' => true,
                ],
            ]);

        $todayCalendar = collect($res->json('data.calendar_days'))->firstWhere('is_today', true);
        $this->assertEquals(2, $todayCalendar['completed_sessions_count']);
        $this->assertTrue($todayCalendar['qualified']);
    }

    public function test_three_consecutive_qualifying_days_gives_current_streak_three(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Day 1: Oct 13
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => Carbon::parse('2026-10-13 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-13 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Day 2: Oct 14
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'progress_data' => ['bead_count' => 108],
            'started_at' => Carbon::parse('2026-10-14 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-14 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Day 3: Oct 15 (today)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::MantraRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'progress_data' => ['repetition_count' => 21],
            'started_at' => Carbon::parse('2026-10-15 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-15 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 3,
                    'longest_streak_days' => 3,
                    'total_qualifying_days' => 3,
                    'last_qualified_date' => '2026-10-15',
                    'has_qualified_today' => true,
                    'awaiting_today' => false,
                    'status' => 'active_today',
                ],
            ]);
    }

    public function test_yesterday_qualifying_today_empty_preserves_streak_and_sets_awaiting_today(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 07:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Day 1: Oct 13
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => Carbon::parse('2026-10-13 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-13 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Day 2: Oct 14 (yesterday)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'progress_data' => ['bead_count' => 108],
            'started_at' => Carbon::parse('2026-10-14 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-14 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Today (Oct 15) has no sessions yet!

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 2,
                    'longest_streak_days' => 2,
                    'total_qualifying_days' => 2,
                    'last_qualified_date' => '2026-10-14',
                    'has_qualified_today' => false,
                    'awaiting_today' => true,
                    'status' => 'awaiting_today',
                ],
            ]);
    }

    public function test_two_missed_days_resets_current_streak_to_zero_and_inactive(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Day: Oct 12
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => Carbon::parse('2026-10-12 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-12 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Oct 13: missed
        // Oct 14: missed
        // Oct 15 (today): missed

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 0,
                    'longest_streak_days' => 1,
                    'total_qualifying_days' => 1,
                    'last_qualified_date' => '2026-10-12',
                    'has_qualified_today' => false,
                    'awaiting_today' => false,
                    'status' => 'inactive',
                ],
            ]);
    }

    public function test_new_activity_after_gap_starts_current_streak_at_one_and_preserves_longest(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Historical run of 3 days: Oct 5, 6, 7
        foreach ([5, 6, 7] as $day) {
            PracticeSession::factory()->create([
                'user_id' => $this->user1->id,
                'practice_type' => PracticeType::Meditation,
                'status' => PracticeSessionStatus::Completed,
                'active_duration_seconds' => 600,
                'started_at' => Carbon::parse("2026-10-0{$day} 08:00:00", 'Asia/Ho_Chi_Minh')->utc(),
                'ended_at' => Carbon::parse("2026-10-0{$day} 08:10:00", 'Asia/Ho_Chi_Minh')->utc(),
            ]);
        }

        // Gap from Oct 8 through 14
        // Today Oct 15 has 1 session
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => Carbon::parse('2026-10-15 08:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-15 08:10:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 1,
                    'longest_streak_days' => 3,
                    'total_qualifying_days' => 4,
                    'last_qualified_date' => '2026-10-15',
                    'has_qualified_today' => true,
                    'awaiting_today' => false,
                    'status' => 'active_today',
                ],
            ]);
    }

    public function test_longest_streak_across_month_boundary(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-02 10:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Sep 29, Sep 30, Oct 01, Oct 02 (4 consecutive days across month boundary)
        $dates = ['2026-09-29', '2026-09-30', '2026-10-01', '2026-10-02'];
        foreach ($dates as $d) {
            PracticeSession::factory()->create([
                'user_id' => $this->user1->id,
                'practice_type' => PracticeType::Meditation,
                'status' => PracticeSessionStatus::Completed,
                'active_duration_seconds' => 600,
                'started_at' => Carbon::parse("{$d} 08:00:00", 'Asia/Ho_Chi_Minh')->utc(),
                'ended_at' => Carbon::parse("{$d} 08:10:00", 'Asia/Ho_Chi_Minh')->utc(),
            ]);
        }

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 4,
                    'longest_streak_days' => 4,
                    'total_qualifying_days' => 4,
                    'last_qualified_date' => '2026-10-02',
                ],
            ]);
    }

    public function test_practice_type_eligibility_rules_filter_accurately(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 12:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // 1. Meditation with 0 active duration (should NOT qualify)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 0,
            'ended_at' => Carbon::parse('2026-10-10 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // 2. Mala with 0 bead count (should NOT qualify)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'progress_data' => ['bead_count' => 0],
            'ended_at' => Carbon::parse('2026-10-11 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // 3. Mantra with 0 repetition count (should NOT qualify)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::MantraRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'progress_data' => ['repetition_count' => 0],
            'ended_at' => Carbon::parse('2026-10-12 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // 4. Bell & Wooden fish even with 100 bells (does NOT qualify per MVP policy)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::BellWoodenFish,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'progress_data' => ['bell_count' => 108, 'wooden_fish_count' => 108],
            'ended_at' => Carbon::parse('2026-10-13 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // 5. Active, Paused, Cancelled, Expired sessions (should NOT qualify)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'active_duration_seconds' => 600,
            'ended_at' => null,
        ]);
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Cancelled,
            'active_duration_seconds' => 600,
            'ended_at' => Carbon::parse('2026-10-14 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Expired,
            'active_duration_seconds' => 600,
            'ended_at' => Carbon::parse('2026-10-14 12:00:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // None of the above should qualify: streak must be 0!
        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJsonPath('data.total_qualifying_days', 0)
            ->assertJsonPath('data.current_streak_days', 0);
    }

    public function test_sutra_reading_and_buddha_name_qualify_properly(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 12:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Day 1: Buddha name recitation with repetitions
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::BuddhaNameRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 400,
            'progress_data' => ['repetition_count' => 108],
            'started_at' => Carbon::parse('2026-10-14 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-14 10:15:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        // Day 2: Sutra recitation with positive active duration
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::SutraRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900,
            'progress_data' => ['completed_sections_count' => 1],
            'started_at' => Carbon::parse('2026-10-15 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-15 10:15:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'current_streak_days' => 2,
                    'longest_streak_days' => 2,
                    'total_qualifying_days' => 2,
                    'has_qualified_today' => true,
                ],
            ]);
    }

    public function test_session_crossing_midnight_counted_on_ended_at_local_date(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 01:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Started at 23:45 on Oct 14, finished at 00:15 on Oct 15 local time
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 1800,
            'started_at' => Carbon::parse('2026-10-14 23:45:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-15 00:15:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'local_today' => '2026-10-15',
                    'current_streak_days' => 1,
                    'has_qualified_today' => true,
                    'last_qualified_date' => '2026-10-15',
                ],
            ]);
    }

    public function test_timezone_aliases_such_as_asia_saigon_are_accepted(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 12:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subMinutes(10)->utc(),
            'ended_at' => $now->copy()->utc(),
        ]);

        $this->getJson('/api/v1/me/practice-streak?timezone=Asia/Saigon')
            ->assertStatus(200)
            ->assertJsonPath('data.timezone', 'Asia/Saigon')
            ->assertJsonPath('data.current_streak_days', 1);
    }

    public function test_profile_timezone_preference_is_used_when_no_query_param_provided(): void
    {
        Sanctum::actingAs($this->user1);

        // Set profile timezone to America/New_York
        $this->user1->profile()->updateOrCreate([], ['timezone' => 'America/New_York']);

        $res = $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJsonPath('data.timezone', 'America/New_York');

        $this->assertNotNull($res->json('data.local_today'));
    }

    public function test_invalid_days_and_timezone_rejected_with_422(): void
    {
        Sanctum::actingAs($this->user1);

        // Invalid days parameter
        $this->getJson('/api/v1/me/practice-streak?days=14')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['days']);

        // Invalid timezone parameter
        $this->getJson('/api/v1/me/practice-streak?timezone=Invalid/Timezone')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    }

    public function test_user_cannot_view_another_users_streak(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 12:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // User 2 has 5 days streak
        foreach ([11, 12, 13, 14, 15] as $day) {
            PracticeSession::factory()->create([
                'user_id' => $this->user2->id,
                'practice_type' => PracticeType::Meditation,
                'status' => PracticeSessionStatus::Completed,
                'active_duration_seconds' => 600,
                'started_at' => Carbon::parse("2026-10-{$day} 08:00:00", 'Asia/Ho_Chi_Minh')->utc(),
                'ended_at' => Carbon::parse("2026-10-{$day} 08:10:00", 'Asia/Ho_Chi_Minh')->utc(),
            ]);
        }

        // User 1 has no sessions
        $this->getJson('/api/v1/me/practice-streak')
            ->assertStatus(200)
            ->assertJsonPath('data.current_streak_days', 0)
            ->assertJsonPath('data.total_qualifying_days', 0);
    }

    public function test_calendar_window_supports_7_30_90_days(): void
    {
        Sanctum::actingAs($this->user1);

        $res7 = $this->getJson('/api/v1/me/practice-streak?days=7')
            ->assertStatus(200);
        $this->assertCount(7, $res7->json('data.calendar_days'));

        $res30 = $this->getJson('/api/v1/me/practice-streak?days=30')
            ->assertStatus(200);
        $this->assertCount(30, $res30->json('data.calendar_days'));

        $res90 = $this->getJson('/api/v1/me/practice-streak?days=90')
            ->assertStatus(200);
        $this->assertCount(90, $res90->json('data.calendar_days'));
    }

    public function test_dst_spring_transition_preserves_streak_continuity(): void
    {
        Sanctum::actingAs($this->user1);

        // In Europe/London, DST spring transition occurs late March (23h day)
        $now = Carbon::parse('2026-03-30 12:00:00', 'Europe/London');
        Carbon::setTestNow($now);

        // March 28, 29 (DST shift), 30
        foreach (['2026-03-28', '2026-03-29', '2026-03-30'] as $dateStr) {
            PracticeSession::factory()->create([
                'user_id' => $this->user1->id,
                'practice_type' => PracticeType::Meditation,
                'status' => PracticeSessionStatus::Completed,
                'active_duration_seconds' => 600,
                'started_at' => Carbon::parse("{$dateStr} 10:00:00", 'Europe/London')->utc(),
                'ended_at' => Carbon::parse("{$dateStr} 10:10:00", 'Europe/London')->utc(),
            ]);
        }

        $res = $this->getJson('/api/v1/me/practice-streak?timezone=Europe/London&days=7')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'timezone' => 'Europe/London',
                    'local_today' => '2026-03-30',
                    'current_streak_days' => 3,
                    'longest_streak_days' => 3,
                    'total_qualifying_days' => 3,
                    'has_qualified_today' => true,
                ],
            ]);

        $calendar = $res->json('data.calendar_days');
        $this->assertCount(7, $calendar);
    }
}

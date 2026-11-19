<?php

namespace Tests\Feature;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticeSession;
use App\Models\Sutra;
use App\Models\SutraReadingProgress;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DharmaJourneyApiTest extends TestCase
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

    public function test_guest_cannot_access_dharma_journey_endpoints(): void
    {
        $this->getJson('/api/v1/me/dharma-journey')
            ->assertStatus(401);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(401);

        $this->getJson('/api/v1/me/dharma-journey/activity')
            ->assertStatus(401);

        $this->getJson('/api/v1/me/dharma-journey/breakdown')
            ->assertStatus(401);
    }

    public function test_new_user_with_no_sessions_returns_zero_metrics(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->getJson('/api/v1/me/dharma-journey/summary?range=30d')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'range' => '30d',
                    'completed_sessions' => 0,
                    'total_active_duration_seconds' => 0,
                    'meditation' => [
                        'sessions' => 0,
                        'active_duration_seconds' => 0,
                    ],
                    'mala' => [
                        'sessions' => 0,
                        'bead_count' => 0,
                        'completed_rounds' => 0,
                    ],
                    'mantra' => [
                        'sessions' => 0,
                        'repetition_count' => 0,
                    ],
                    'buddha_name' => [
                        'sessions' => 0,
                        'repetition_count' => 0,
                    ],
                    'sutra' => [
                        'sessions' => 0,
                        'active_duration_seconds' => 0,
                    ],
                    'bell_wooden_fish' => [
                        'sessions' => 0,
                        'bell_count' => 0,
                        'wooden_fish_count' => 0,
                    ],
                ],
            ]);

        $this->assertNotNull($res->json('data.date_from'));
        $this->assertNotNull($res->json('data.date_to'));
    }

    public function test_only_completed_sessions_are_included_in_aggregation(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 12:00:00', 'UTC');
        Carbon::setTestNow($now);

        // 1 Active session for user1 (should be excluded)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'active_duration_seconds' => 300,
            'started_at' => $now->copy()->subMinutes(10),
            'ended_at' => null,
        ]);

        // 1 Paused session for another user or verify paused separately (user can only have 1 open session)
        PracticeSession::factory()->create([
            'user_id' => $this->user2->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Paused,
            'active_duration_seconds' => 400,
            'started_at' => $now->copy()->subHours(2),
            'ended_at' => null,
        ]);

        // 1 Cancelled session (should be excluded)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Cancelled,
            'active_duration_seconds' => 500,
            'started_at' => $now->copy()->subHours(4),
            'ended_at' => $now->copy()->subHours(3),
        ]);

        // 1 Expired session (should be excluded)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Expired,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subHours(6),
            'ended_at' => $now->copy()->subHours(5),
        ]);

        // 1 Completed session (should be INCLUDED)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900,
            'started_at' => $now->copy()->subMinutes(20),
            'ended_at' => $now->copy()->subMinutes(5),
        ]);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 1)
            ->assertJsonPath('data.total_active_duration_seconds', 900)
            ->assertJsonPath('data.meditation.sessions', 1)
            ->assertJsonPath('data.meditation.active_duration_seconds', 900);
    }

    public function test_active_duration_is_used_not_planned_duration(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900, // 15 mins actual
            'progress_data' => ['planned_duration_seconds' => 3600], // 60 mins planned
            'started_at' => $now->copy()->subMinutes(20),
            'ended_at' => $now,
        ]);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 1)
            ->assertJsonPath('data.total_active_duration_seconds', 900)
            ->assertJsonPath('data.meditation.active_duration_seconds', 900);
    }

    public function test_multiple_session_types_counted_without_double_counting_duration(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Session 1: Meditation, 900s
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900,
            'started_at' => $now->copy()->subHours(3),
            'ended_at' => $now->copy()->subHours(3)->addSeconds(900),
        ]);

        // Session 2: Mantra, 600s with 108 repetitions
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::MantraRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'progress_data' => ['repetition_count' => 108],
            'started_at' => $now->copy()->subHours(1),
            'ended_at' => $now->copy()->subHours(1)->addSeconds(600),
        ]);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 2)
            ->assertJsonPath('data.total_active_duration_seconds', 1500) // 900 + 600 = 1500 (NO 108 added to duration!)
            ->assertJsonPath('data.meditation.sessions', 1)
            ->assertJsonPath('data.meditation.active_duration_seconds', 900)
            ->assertJsonPath('data.mantra.sessions', 1)
            ->assertJsonPath('data.mantra.repetition_count', 108);
    }

    public function test_mala_bead_count_and_rounds_rule_a_derived_per_session(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Session 1: 80 beads (0 full rounds)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'progress_data' => ['bead_count' => 80],
            'started_at' => $now->copy()->subHours(3),
            'ended_at' => $now->copy()->subHours(3)->addMinutes(5),
        ]);

        // Session 2: 80 beads (0 full rounds)
        // Two 80-bead sessions total 160 beads, but 0 completed rounds according to Rule A!
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'progress_data' => ['bead_count' => 80],
            'started_at' => $now->copy()->subHours(2),
            'ended_at' => $now->copy()->subHours(2)->addMinutes(5),
        ]);

        $res1 = $this->getJson('/api/v1/me/dharma-journey/summary');
        $res1->assertStatus(200)
            ->assertJsonPath('data.mala.sessions', 2)
            ->assertJsonPath('data.mala.bead_count', 160)
            ->assertJsonPath('data.mala.completed_rounds', 0); // 0 + 0 = 0 rounds!

        // Session 3: 216 beads (exactly 2 full rounds: 216 / 108 = 2)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900,
            'progress_data' => ['bead_count' => 216],
            'started_at' => $now->copy()->subHours(1),
            'ended_at' => $now->copy()->subHours(1)->addMinutes(15),
        ]);

        $res2 = $this->getJson('/api/v1/me/dharma-journey/summary');
        $res2->assertStatus(200)
            ->assertJsonPath('data.mala.sessions', 3)
            ->assertJsonPath('data.mala.bead_count', 376) // 80 + 80 + 216 = 376
            ->assertJsonPath('data.mala.completed_rounds', 2); // 0 + 0 + 2 = 2 rounds!
    }

    public function test_mantra_and_buddha_name_manual_repetitions_summed_separately(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Mantra session
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::MantraRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 400,
            'progress_data' => ['repetition_count' => 108],
            'started_at' => $now->copy()->subHours(2),
            'ended_at' => $now->copy()->subHours(2)->addSeconds(400),
        ]);

        // Buddha name session
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::BuddhaNameRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 500,
            'progress_data' => ['repetition_count' => 324],
            'started_at' => $now->copy()->subHours(1),
            'ended_at' => $now->copy()->subHours(1)->addSeconds(500),
        ]);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.mantra.sessions', 1)
            ->assertJsonPath('data.mantra.repetition_count', 108)
            ->assertJsonPath('data.buddha_name.sessions', 1)
            ->assertJsonPath('data.buddha_name.repetition_count', 324);
    }

    public function test_bell_and_wooden_fish_counts_aggregated_safely(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::BellWoodenFish,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 350,
            'progress_data' => [
                'bell_count' => 18,
                'wooden_fish_count' => 108,
            ],
            'started_at' => $now->copy()->subHours(1),
            'ended_at' => $now->copy()->subHours(1)->addSeconds(350),
        ]);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.bell_wooden_fish.sessions', 1)
            ->assertJsonPath('data.bell_wooden_fish.bell_count', 18)
            ->assertJsonPath('data.bell_wooden_fish.wooden_fish_count', 108);
    }

    public function test_sutra_practice_sessions_counted_separately_from_reading_progress(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // 1 Completed PracticeSession for SutraReading
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::SutraReading,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 1200,
            'started_at' => $now->copy()->subHours(2),
            'ended_at' => $now->copy()->subHours(2)->addSeconds(1200),
        ]);

        // Also create a SutraReadingProgress record (from Task 24)
        $tradition = Tradition::factory()->create(['name' => 'Bắc Tông']);
        $sutra = Sutra::factory()->create(['tradition_id' => $tradition->id]);
        $version = SutraVersion::factory()->create(['sutra_id' => $sutra->id]);
        $section = SutraSection::factory()->create(['sutra_version_id' => $version->id]);

        SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $sutra->id,
            'sutra_version_id' => $version->id,
            'sutra_section_id' => $section->id,
            'scroll_percentage' => 45,
            'revision' => 1,
            'last_read_at' => $now,
        ]);

        // The summary MUST only count the 1 PracticeSession, NOT the SutraReadingProgress!
        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 1)
            ->assertJsonPath('data.total_active_duration_seconds', 1200)
            ->assertJsonPath('data.sutra.sessions', 1)
            ->assertJsonPath('data.sutra.active_duration_seconds', 1200);
    }

    public function test_missing_or_corrupt_progress_data_handled_safely(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Null progress_data
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'progress_data' => null,
            'started_at' => $now->copy()->subHours(3),
            'ended_at' => $now->copy()->subHours(3)->addMinutes(5),
        ]);

        // Malformed/string progress_data
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::MantraRecitation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'progress_data' => ['repetition_count' => 'invalid_string'],
            'started_at' => $now->copy()->subHours(2),
            'ended_at' => $now->copy()->subHours(2)->addMinutes(5),
        ]);

        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 2)
            ->assertJsonPath('data.mala.bead_count', 0)
            ->assertJsonPath('data.mantra.repetition_count', 0);
    }

    public function test_user_isolation_user_a_cannot_see_user_b_metrics(): void
    {
        $now = Carbon::parse('2026-10-15 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // User 1 has 3 completed sessions (1800s total)
        PracticeSession::factory()->count(3)->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subHours(2),
            'ended_at' => $now->copy()->subHours(1),
        ]);

        // User 2 has 1 completed session (300s total)
        PracticeSession::factory()->create([
            'user_id' => $this->user2->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 300,
            'started_at' => $now->copy()->subHours(3),
            'ended_at' => $now->copy()->subHours(2),
        ]);

        // Request as User 2: must only see 1 session, 300s
        Sanctum::actingAs($this->user2);
        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 1)
            ->assertJsonPath('data.total_active_duration_seconds', 300);

        // Request as User 1: must see 3 sessions, 1800s
        Sanctum::actingAs($this->user1);
        $this->getJson('/api/v1/me/dharma-journey/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 3)
            ->assertJsonPath('data.total_active_duration_seconds', 1800);
    }

    public function test_time_range_filtering_and_boundaries(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 12:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Session A: 3 days ago (in 7d, 30d, 90d, all)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subDays(3)->subMinutes(10)->utc(),
            'ended_at' => $now->copy()->subDays(3)->utc(),
        ]);

        // Session B: 15 days ago (NOT in 7d; in 30d, 90d, all)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subDays(15)->subMinutes(10)->utc(),
            'ended_at' => $now->copy()->subDays(15)->utc(),
        ]);

        // Session C: 60 days ago (NOT in 7d, NOT in 30d; in 90d, all)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subDays(60)->subMinutes(10)->utc(),
            'ended_at' => $now->copy()->subDays(60)->utc(),
        ]);

        // Session D: 150 days ago (NOT in 7d, 30d, 90d; only in all)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'started_at' => $now->copy()->subDays(150)->subMinutes(10)->utc(),
            'ended_at' => $now->copy()->subDays(150)->utc(),
        ]);

        // 7 days filter: only Session A
        $this->getJson('/api/v1/me/dharma-journey/summary?range=7d&timezone=Asia/Ho_Chi_Minh')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 1);

        // 30 days filter: Sessions A, B
        $this->getJson('/api/v1/me/dharma-journey/summary?range=30d&timezone=Asia/Ho_Chi_Minh')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 2);

        // 90 days filter: Sessions A, B, C
        $this->getJson('/api/v1/me/dharma-journey/summary?range=90d&timezone=Asia/Ho_Chi_Minh')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 3);

        // All-time filter: Sessions A, B, C, D
        $this->getJson('/api/v1/me/dharma-journey/summary?range=all&timezone=Asia/Ho_Chi_Minh')
            ->assertStatus(200)
            ->assertJsonPath('data.completed_sessions', 4);
    }

    public function test_invalid_range_and_timezone_rejected_with_422(): void
    {
        Sanctum::actingAs($this->user1);

        // Invalid range
        $this->getJson('/api/v1/me/dharma-journey/summary?range=100d')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['range']);

        // Invalid timezone
        $this->getJson('/api/v1/me/dharma-journey/summary?timezone=Invalid/Timezone')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    }

    public function test_timezone_aliases_such_as_asia_saigon_are_accepted_and_normalized(): void
    {
        Sanctum::actingAs($this->user1);

        $this->getJson('/api/v1/me/dharma-journey?range=30d&timezone=Asia/Saigon')
            ->assertStatus(200)
            ->assertJsonPath('data.summary.timezone', 'Asia/Saigon');

        $this->getJson('/api/v1/me/dharma-journey/summary?range=30d&timezone=Asia/Saigon')
            ->assertStatus(200)
            ->assertJsonPath('data.timezone', 'Asia/Saigon');
    }

    public function test_activity_chart_zero_fills_continuous_days(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 15:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($now);

        // Create 1 session on 2026-10-13 (2 days ago in Vietnam time)
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 900,
            'started_at' => Carbon::parse('2026-10-13 10:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'ended_at' => Carbon::parse('2026-10-13 10:15:00', 'Asia/Ho_Chi_Minh')->utc(),
        ]);

        $res = $this->getJson('/api/v1/me/dharma-journey/activity?range=7d&timezone=Asia/Ho_Chi_Minh')
            ->assertStatus(200)
            ->assertJsonPath('data.range', '7d')
            ->assertJsonPath('data.granularity', 'day');

        $points = $res->json('data.points');
        $this->assertCount(7, $points);

        // Ensure 2026-10-13 has 1 session, other days have 0
        $point13 = collect($points)->firstWhere('date', '2026-10-13');
        $this->assertNotNull($point13);
        $this->assertEquals(1, $point13['completed_sessions']);
        $this->assertEquals(900, $point13['active_duration_seconds']);

        $point14 = collect($points)->firstWhere('date', '2026-10-14');
        $this->assertNotNull($point14);
        $this->assertEquals(0, $point14['completed_sessions']);
        $this->assertEquals(0, $point14['active_duration_seconds']);
    }

    public function test_breakdown_endpoint_returns_supported_practice_types(): void
    {
        Sanctum::actingAs($this->user1);
        $now = Carbon::parse('2026-10-15 15:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Mala session
        PracticeSession::factory()->create([
            'user_id' => $this->user1->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Completed,
            'active_duration_seconds' => 600,
            'progress_data' => ['bead_count' => 108],
            'started_at' => $now->copy()->subHours(1),
            'ended_at' => $now,
        ]);

        $res = $this->getJson('/api/v1/me/dharma-journey/breakdown?range=30d')
            ->assertStatus(200)
            ->assertJsonPath('data.range', '30d');

        $items = collect($res->json('data.items'));
        $malaItem = $items->firstWhere('practice_type', 'mala');

        $this->assertNotNull($malaItem);
        $this->assertEquals('Tràng Hạt', $malaItem['label']);
        $this->assertEquals(1, $malaItem['completed_sessions']);
        $this->assertEquals(600, $malaItem['active_duration_seconds']);
        $this->assertEquals(108, $malaItem['metrics']['bead_count']);
        $this->assertEquals(1, $malaItem['metrics']['completed_rounds']);
    }

    public function test_complete_dashboard_index_endpoint_returns_composite_payload(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->getJson('/api/v1/me/dharma-journey?range=30d')
            ->assertStatus(200);

        $this->assertNotNull($res->json('data.summary'));
        $this->assertNotNull($res->json('data.activity'));
        $this->assertNotNull($res->json('data.breakdown'));
    }
}

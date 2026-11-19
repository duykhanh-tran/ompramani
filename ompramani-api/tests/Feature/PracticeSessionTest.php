<?php

namespace Tests\Feature;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\PracticeSession;
use App\Models\Sutra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PracticeSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    /**
     * 1. Guest cannot start a practice session.
     */
    public function test_01_guest_cannot_start_session(): void
    {
        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'meditation',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 2. Authenticated user can start a practice session.
     */
    public function test_02_auth_user_can_start_session(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'meditation',
            'progress_data' => [
                'planned_duration_seconds' => 900,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.practice_type', 'meditation')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.active_duration_seconds', 0)
            ->assertJsonPath('data.progress_data.planned_duration_seconds', 900);

        $this->assertDatabaseHas('practice_sessions', [
            'user_id' => $user->id,
            'practice_type' => 'meditation',
            'status' => 'active',
        ]);
    }

    /**
     * 3. Server automatically assigns user_id from the authenticated user.
     */
    public function test_03_server_sets_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'meditation',
            'user_id' => $otherUser->id, // Attempt to spoof user_id
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('practice_sessions', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('practice_sessions', [
            'id' => $response->json('data.id'),
            'user_id' => $otherUser->id,
        ]);
    }

    /**
     * 4. Server sets started_at from the server clock.
     */
    public function test_04_server_sets_started_at_from_server_clock(): void
    {
        $fixedTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($fixedTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'meditation',
            'started_at' => '2020-01-01T00:00:00Z', // Attempt to spoof client started_at
        ]);

        $response->assertStatus(201);
        $this->assertEquals($fixedTime->toISOString(), $response->json('data.started_at'));
    }

    /**
     * 5. Invalid practice_type is rejected with 422.
     */
    public function test_05_invalid_practice_type_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'super_fast_chanting',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['practice_type']);
    }

    /**
     * 6. Invalid subject_type is rejected with 422.
     */
    public function test_06_invalid_subject_type_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'mantra_recitation',
            'subject_type' => 'unsupported_model_class',
            'subject_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject_type']);
    }

    /**
     * 7. Draft / unpublished Buddhist content cannot be referenced.
     */
    public function test_07_draft_content_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $draftMantra = Mantra::factory()->create([
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'mantra_recitation',
            'subject_type' => 'mantra',
            'subject_id' => $draftMantra->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject_id']);
    }

    /**
     * 8. User cannot access or manipulate another user's session.
     */
    public function test_08_user_cannot_access_another_users_session(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $session = PracticeSession::factory()->create([
            'user_id' => $owner->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
        ]);

        Sanctum::actingAs($stranger);

        $this->getJson(route('api.v1.practice-sessions.show', $session->id))
            ->assertStatus(404);

        $this->postJson(route('api.v1.practice-sessions.pause', $session->id))
            ->assertStatus(404);

        $this->postJson(route('api.v1.practice-sessions.complete', $session->id))
            ->assertStatus(404);
    }

    /**
     * 9. Valid pause credits elapsed time and sets status to paused.
     */
    public function test_09_valid_pause(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'started_at' => $baseTime,
            'last_heartbeat_at' => $baseTime,
            'active_duration_seconds' => 0,
        ]);

        // Advance 45 seconds
        Carbon::setTestNow($baseTime->copy()->addSeconds(45));

        $response = $this->postJson(route('api.v1.practice-sessions.pause', $session->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paused')
            ->assertJsonPath('data.active_duration_seconds', 45);

        $this->assertDatabaseHas('practice_sessions', [
            'id' => $session->id,
            'status' => 'paused',
            'active_duration_seconds' => 45,
            'last_heartbeat_at' => null,
        ]);
    }

    /**
     * 10. Valid resume sets status to active and resets checkpoint.
     */
    public function test_10_valid_resume(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->paused()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'started_at' => $baseTime->copy()->subMinutes(10),
            'active_duration_seconds' => 60,
        ]);

        $response = $this->postJson(route('api.v1.practice-sessions.resume', $session->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.active_duration_seconds', 60);

        $session->refresh();
        $this->assertEquals(PracticeSessionStatus::Active, $session->status);
        $this->assertNotNull($session->last_heartbeat_at);
        $this->assertEquals($baseTime->toISOString(), $session->last_heartbeat_at->toISOString());
    }

    /**
     * 11. Valid complete finalizes duration, records ended_at, and sets status to completed.
     */
    public function test_11_valid_complete(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'started_at' => $baseTime,
            'last_heartbeat_at' => $baseTime,
            'active_duration_seconds' => 100,
        ]);

        // Advance 50 seconds
        $completeTime = $baseTime->copy()->addSeconds(50);
        Carbon::setTestNow($completeTime);

        $response = $this->postJson(route('api.v1.practice-sessions.complete', $session->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.active_duration_seconds', 150)
            ->assertJsonPath('data.ended_at', $completeTime->toISOString());

        $this->assertDatabaseHas('practice_sessions', [
            'id' => $session->id,
            'status' => 'completed',
            'active_duration_seconds' => 150,
        ]);
    }

    /**
     * 12. Valid cancel sets status to cancelled and preserves audit history.
     */
    public function test_12_valid_cancel(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'status' => PracticeSessionStatus::Active,
        ]);

        $response = $this->postJson(route('api.v1.practice-sessions.cancel', $session->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('practice_sessions', [
            'id' => $session->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * 13. Invalid status transitions are rejected.
     */
    public function test_13_invalid_status_transitions_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $activeSession = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'status' => PracticeSessionStatus::Active,
        ]);

        // Cannot resume an active session
        $this->postJson(route('api.v1.practice-sessions.resume', $activeSession->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $activeSession->update(['status' => PracticeSessionStatus::Completed]);

        // Cannot pause or resume a completed session
        $this->postJson(route('api.v1.practice-sessions.pause', $activeSession->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->postJson(route('api.v1.practice-sessions.resume', $activeSession->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * 14. Completed session cannot receive further progress updates.
     */
    public function test_14_completed_session_cannot_receive_progress_update(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->completed()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Mala,
            'progress_data' => ['bead_count' => 108],
        ]);

        $response = $this->patchJson(route('api.v1.practice-sessions.progress', $session->id), [
            'progress_data' => ['bead_count' => 150],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * 15. Retry start with same client_session_key returns existing session without duplication.
     */
    public function test_15_retry_start_does_not_create_duplicate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $idempotencyKey = (string) Str::uuid();

        $response1 = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'meditation',
            'client_session_key' => $idempotencyKey,
            'progress_data' => ['planned_duration_seconds' => 900],
        ]);

        $response1->assertStatus(201);
        $sessionId = $response1->json('data.id');

        // Retry with same client_session_key
        $response2 = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'meditation',
            'client_session_key' => $idempotencyKey,
            'progress_data' => ['planned_duration_seconds' => 900],
        ]);

        $response2->assertStatus(200);
        $this->assertEquals($sessionId, $response2->json('data.id'));
        $this->assertEquals(1, PracticeSession::where('user_id', $user->id)->count());
    }

    /**
     * 16. Retry complete does not double-credit duration or metrics.
     */
    public function test_16_retry_complete_does_not_double_credit(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'started_at' => $baseTime,
            'last_heartbeat_at' => $baseTime,
            'active_duration_seconds' => 60,
        ]);

        Carbon::setTestNow($baseTime->copy()->addSeconds(30));

        // First complete
        $res1 = $this->postJson(route('api.v1.practice-sessions.complete', $session->id));
        $res1->assertStatus(200)->assertJsonPath('data.active_duration_seconds', 90);

        // Advance further time and retry complete
        Carbon::setTestNow($baseTime->copy()->addMinutes(10));
        $res2 = $this->postJson(route('api.v1.practice-sessions.complete', $session->id));

        $res2->assertStatus(200)->assertJsonPath('data.active_duration_seconds', 90);
    }

    /**
     * 17. Stale progress update (with smaller or equal sequence) is rejected.
     */
    public function test_17_stale_progress_update_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->mala(50)->create([
            'user_id' => $user->id,
            'sequence' => 3,
        ]);

        // Submit with sequence = 3 (equal, stale)
        $response = $this->patchJson(route('api.v1.practice-sessions.progress', $session->id), [
            'progress_data' => ['bead_count' => 55],
            'sequence' => 3,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sequence']);

        // Submit with sequence = 4 (fresh, valid)
        $validRes = $this->patchJson(route('api.v1.practice-sessions.progress', $session->id), [
            'progress_data' => ['bead_count' => 60],
            'sequence' => 4,
        ]);

        $validRes->assertStatus(200)
            ->assertJsonPath('data.sequence', 4)
            ->assertJsonPath('data.progress_data.bead_count', 60);
    }

    /**
     * 18. Duration does not increase while paused.
     */
    public function test_18_duration_does_not_increase_while_paused(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->paused()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'active_duration_seconds' => 120,
        ]);

        // Advance 2 hours while paused
        Carbon::setTestNow($baseTime->copy()->addHours(2));

        // Attempting heartbeat while paused must be rejected
        $this->postJson(route('api.v1.practice-sessions.heartbeat', $session->id))
            ->assertStatus(422);

        $session->refresh();
        $this->assertEquals(120, $session->active_duration_seconds);
    }

    /**
     * 19. Heartbeat caps credit and does not add unbounded duration.
     */
    public function test_19_heartbeat_caps_credit(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
            'started_at' => $baseTime,
            'last_heartbeat_at' => $baseTime,
            'active_duration_seconds' => 0,
        ]);

        // Advance 30 seconds -> credits exactly 30 seconds
        Carbon::setTestNow($baseTime->copy()->addSeconds(30));
        $res1 = $this->postJson(route('api.v1.practice-sessions.heartbeat', $session->id));
        $res1->assertStatus(200)->assertJsonPath('data.active_duration_seconds', 30);

        // Advance 1 hour (3600 seconds) -> clamped to max 120 seconds!
        Carbon::setTestNow($baseTime->copy()->addSeconds(30)->addHours(1));
        $res2 = $this->postJson(route('api.v1.practice-sessions.heartbeat', $session->id));
        $res2->assertStatus(200)->assertJsonPath('data.active_duration_seconds', 150); // 30 + 120
    }

    /**
     * 20. Expired session is not treated as completed.
     */
    public function test_20_expired_session_is_not_treated_as_completed(): void
    {
        $baseTime = Carbon::create(2026, 10, 14, 10, 0, 0, 'UTC');
        Carbon::setTestNow($baseTime);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'status' => PracticeSessionStatus::Active,
            'started_at' => $baseTime->copy()->subHours(2),
            'last_activity_at' => $baseTime->copy()->subHours(2),
        ]);

        // Calling active check auto-expires the stale session
        $this->getJson(route('api.v1.practice-sessions.active'))
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $session->refresh();
        $this->assertEquals(PracticeSessionStatus::Expired, $session->status);
        $this->assertNotEquals(PracticeSessionStatus::Completed, $session->status);
    }

    /**
     * 21. Mala 108 beads derives exactly 1 completed round.
     */
    public function test_21_mala_108_beads_equals_one_round(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->mala(108)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(route('api.v1.practice-sessions.show', $session->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.derived_metrics.completed_rounds', 1)
            ->assertJsonPath('data.derived_metrics.remaining_beads', 0);
    }

    /**
     * 22. Mala 216 beads derives exactly 2 completed rounds.
     */
    public function test_22_mala_216_beads_equals_two_rounds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->mala(216)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(route('api.v1.practice-sessions.show', $session->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.derived_metrics.completed_rounds', 2)
            ->assertJsonPath('data.derived_metrics.remaining_beads', 0);
    }

    /**
     * 23. Progress counter doesn't double count on retry.
     */
    public function test_23_progress_counter_does_not_double_count(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->mala(50)->create([
            'user_id' => $user->id,
            'sequence' => 1,
        ]);

        // Send absolute count 108 with sequence 2
        $this->patchJson(route('api.v1.practice-sessions.progress', $session->id), [
            'progress_data' => ['bead_count' => 108],
            'sequence' => 2,
        ])->assertStatus(200)->assertJsonPath('data.progress_data.bead_count', 108);

        // Client network retried the exact same payload
        $this->patchJson(route('api.v1.practice-sessions.progress', $session->id), [
            'progress_data' => ['bead_count' => 108],
            'sequence' => 2,
        ])->assertStatus(422); // Stale sequence rejected, database remains 108!

        $session->refresh();
        $this->assertEquals(108, $session->progress_data['bead_count']);
    }

    /**
     * 24. Session history returns only the authenticated user's records.
     */
    public function test_24_session_history_returns_only_own_records(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PracticeSession::factory()->completed()->count(3)->create(['user_id' => $user1->id]);
        PracticeSession::factory()->completed()->count(2)->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->getJson(route('api.v1.practice-sessions.index'));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * 25. Pagination and filters work properly.
     */
    public function test_25_pagination_and_filters_work(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        PracticeSession::factory()->count(5)->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Completed,
        ]);

        PracticeSession::factory()->count(2)->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Cancelled,
        ]);

        $response = $this->getJson(route('api.v1.practice-sessions.index', [
            'practice_type' => 'meditation',
            'status' => 'completed',
            'per_page' => 3,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    /**
     * 26. Concurrent start is protected: user cannot have two open sessions.
     */
    public function test_26_concurrent_start_protected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Meditation,
            'status' => PracticeSessionStatus::Active,
        ]);

        // Attempting to start a second session must be rejected with 422
        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'mala',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['practice_session']);
    }

    /**
     * 27. Published Buddhist content can be properly linked to session.
     */
    public function test_27_published_buddhist_figure_can_be_linked(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $figure = BuddhistFigure::factory()->create([
            'status' => 'published',
            'name' => 'Quán Thế Âm Bồ Tát',
        ]);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'buddha_name_recitation',
            'subject_type' => 'buddhist_figure',
            'subject_id' => $figure->id,
            'progress_data' => ['repetition_count' => 108],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject.type', 'buddhist_figure')
            ->assertJsonPath('data.subject.title', 'Quán Thế Âm Bồ Tát');
    }

    /**
     * 28. Published Sutra can be properly linked to session.
     */
    public function test_28_published_sutra_can_be_linked(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $sutra = Sutra::factory()->create([
            'status' => 'published',
            'title' => 'Kinh Bát Nhã Ba La Mật Đa',
        ]);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'sutra_recitation',
            'subject_type' => 'sutra',
            'subject_id' => $sutra->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject.type', 'sutra')
            ->assertJsonPath('data.subject.title', 'Kinh Bát Nhã Ba La Mật Đa');
    }

    /**
     * 29. Mala counting logic rules: 0, 1, 107, 108, 109, 215, 216, 324 beads.
     */
    public function test_29_mala_complete_counting_rules(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $testCases = [
            0 => ['rounds' => 0, 'remaining' => 0],
            1 => ['rounds' => 0, 'remaining' => 1],
            107 => ['rounds' => 0, 'remaining' => 107],
            108 => ['rounds' => 1, 'remaining' => 0],
            109 => ['rounds' => 1, 'remaining' => 1],
            215 => ['rounds' => 1, 'remaining' => 107],
            216 => ['rounds' => 2, 'remaining' => 0],
            324 => ['rounds' => 3, 'remaining' => 0],
        ];

        foreach ($testCases as $beads => $expected) {
            $session = PracticeSession::factory()->completed()->mala($beads)->create([
                'user_id' => $user->id,
            ]);

            $this->assertSame($expected['rounds'], $session->completed_rounds, "Failed rounds check for {$beads} beads");
            $this->assertSame($expected['remaining'], $session->remaining_beads, "Failed remaining check for {$beads} beads");

            $metrics = $session->deriveMetrics();
            $this->assertSame($expected['rounds'], $metrics['completed_rounds']);
            $this->assertSame($expected['remaining'], $metrics['remaining_beads']);
        }
    }

    /**
     * 30. Negative bead count is rejected by validation.
     */
    public function test_30_mala_negative_bead_count_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'practice_type' => PracticeType::Mala,
            'status' => PracticeSessionStatus::Active,
        ]);

        $response = $this->patchJson(route('api.v1.practice-sessions.progress', $session->id), [
            'progress_data' => ['bead_count' => -5],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress_data.bead_count']);
    }

    /**
     * 31. Mala session can be linked with a published Mantra.
     */
    public function test_31_mala_with_mantra_subject(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mantra = Mantra::factory()->create([
            'status' => 'published',
            'title' => 'Chú Đại Bi',
        ]);

        $response = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'mala',
            'subject_type' => 'mantra',
            'subject_id' => $mantra->id,
            'progress_data' => ['bead_count' => 0],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.practice_type', 'mala')
            ->assertJsonPath('data.subject.type', 'mantra')
            ->assertJsonPath('data.subject.title', 'Chú Đại Bi');
    }

    /**
     * 32. Mala full lifecycle: Start -> 135 beads (1 round + 27 beads) -> Pause -> Resume -> Complete.
     */
    public function test_32_mala_full_lifecycle(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 1. Start session
        $startRes = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'mala',
            'progress_data' => ['bead_count' => 0],
        ]);
        $startRes->assertStatus(201);
        $sessionId = $startRes->json('data.id');

        // 2. Advance to 108 beads (Round 1)
        $this->patchJson(route('api.v1.practice-sessions.progress', $sessionId), [
            'progress_data' => ['bead_count' => 108],
            'sequence' => 1,
        ])->assertStatus(200)
            ->assertJsonPath('data.progress_data.bead_count', 108)
            ->assertJsonPath('data.derived_metrics.completed_rounds', 1)
            ->assertJsonPath('data.derived_metrics.remaining_beads', 0);

        // 3. Advance to 135 beads (Round 1 + 27 beads)
        $this->patchJson(route('api.v1.practice-sessions.progress', $sessionId), [
            'progress_data' => ['bead_count' => 135],
            'sequence' => 2,
        ])->assertStatus(200)
            ->assertJsonPath('data.progress_data.bead_count', 135)
            ->assertJsonPath('data.derived_metrics.completed_rounds', 1)
            ->assertJsonPath('data.derived_metrics.remaining_beads', 27);

        // 4. Pause session
        $this->postJson(route('api.v1.practice-sessions.pause', $sessionId))
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');

        // 5. Resume session
        $this->postJson(route('api.v1.practice-sessions.resume', $sessionId))
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        // 6. Complete session with final count
        $completeRes = $this->postJson(route('api.v1.practice-sessions.complete', $sessionId), [
            'progress_data' => ['bead_count' => 135],
        ]);

        $completeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress_data.bead_count', 135)
            ->assertJsonPath('data.derived_metrics.completed_rounds', 1)
            ->assertJsonPath('data.derived_metrics.remaining_beads', 27);
    }

    /**
     * 39. Full bell & wooden fish practice session lifecycle with metrics.
     */
    public function test_39_bell_wooden_fish_lifecycle_and_metrics(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 1. Start standalone bell_wooden_fish session
        $startRes = $this->postJson(route('api.v1.practice-sessions.store'), [
            'practice_type' => 'bell_wooden_fish',
            'progress_data' => [
                'bell_count' => 0,
                'wooden_fish_count' => 0,
            ],
        ]);
        $startRes->assertStatus(201)
            ->assertJsonPath('data.practice_type', 'bell_wooden_fish')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.progress_data.bell_count', 0)
            ->assertJsonPath('data.progress_data.wooden_fish_count', 0)
            ->assertJsonPath('data.derived_metrics.bell_count', 0)
            ->assertJsonPath('data.derived_metrics.wooden_fish_count', 0);

        $sessionId = $startRes->json('data.id');

        // 2. Update progress: 7 bells, 108 wooden fish strikes
        $progressRes = $this->patchJson(route('api.v1.practice-sessions.progress', $sessionId), [
            'progress_data' => [
                'bell_count' => 7,
                'wooden_fish_count' => 108,
            ],
            'sequence' => 1,
        ]);
        $progressRes->assertStatus(200)
            ->assertJsonPath('data.progress_data.bell_count', 7)
            ->assertJsonPath('data.progress_data.wooden_fish_count', 108)
            ->assertJsonPath('data.derived_metrics.bell_count', 7)
            ->assertJsonPath('data.derived_metrics.wooden_fish_count', 108);

        // 3. Complete session
        $completeRes = $this->postJson(route('api.v1.practice-sessions.complete', $sessionId), [
            'progress_data' => [
                'bell_count' => 12,
                'wooden_fish_count' => 216,
            ],
        ]);
        $completeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress_data.bell_count', 12)
            ->assertJsonPath('data.progress_data.wooden_fish_count', 216)
            ->assertJsonPath('data.derived_metrics.bell_count', 12)
            ->assertJsonPath('data.derived_metrics.wooden_fish_count', 216);
    }
}

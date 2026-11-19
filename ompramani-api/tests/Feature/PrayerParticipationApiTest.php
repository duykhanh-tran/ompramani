<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerVisibility;
use App\Models\PracticeSession;
use App\Models\PrayerIntention;
use App\Models\PrayerParticipation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerParticipationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createPublicApprovedIntention(User $author): PrayerIntention
    {
        return PrayerIntention::create([
            'user_id' => $author->id,
            'title' => 'Nguyện cho thế giới hòa bình',
            'body' => 'Nguyện mười phương chư Phật gia hộ cho chúng sinh dứt trừ khổ đau, sống đời an lạc.',
            'category' => PrayerCategory::BinhAn->value,
            'visibility' => PrayerVisibility::Public->value,
            'moderation_status' => PrayerModerationStatus::Approved->value,
            'published_at' => now()->subHour(),
            'approved_at' => now()->subHour(),
        ]);
    }

    // ==========================================
    // 1. SECURITY & AUTHENTICATION TESTS
    // ==========================================

    public function test_guest_cannot_join_prayer_intention(): void
    {
        $author = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        $response = $this->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(401);
    }

    public function test_guest_cannot_withdraw_participation(): void
    {
        $author = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        $response = $this->deleteJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(401);
    }

    public function test_guest_can_see_public_count(): void
    {
        $author = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);
        $user1 = User::factory()->create();

        PrayerParticipation::create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $user1->id,
            'participated_at' => now(),
            'withdrawn_at' => null,
        ]);

        $response = $this->getJson("/api/v1/prayer-intentions/{$intention->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.participation_count', 1);
    }

    public function test_user_can_join_public_approved_intention(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.public_id', $intention->public_id)
            ->assertJsonPath('data.is_participating', true)
            ->assertJsonPath('data.participation_count', 1);

        $this->assertDatabaseHas('prayer_participations', [
            'prayer_intention_id' => $intention->id,
            'user_id' => $user->id,
            'withdrawn_at' => null,
        ]);
    }

    public function test_owner_cannot_self_participate(): void
    {
        $owner = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($owner);

        $response = $this->actingAs($owner)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(422);
        $this->assertDatabaseMissing('prayer_participations', [
            'prayer_intention_id' => $intention->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_user_cannot_join_private_intention(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $privateIntention = PrayerIntention::create([
            'user_id' => $author->id,
            'title' => 'Riêng tư',
            'body' => 'Lời nguyện riêng tư không chia sẻ.',
            'visibility' => PrayerVisibility::Private->value,
            'moderation_status' => PrayerModerationStatus::NotRequired->value,
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$privateIntention->public_id}");

        $response->assertStatus(404);
        $this->assertDatabaseCount('prayer_participations', 0);
    }

    public function test_user_cannot_join_pending_intention(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $pendingIntention = PrayerIntention::create([
            'user_id' => $author->id,
            'title' => 'Đang chờ duyệt',
            'body' => 'Lời nguyện gửi công khai chờ duyệt.',
            'visibility' => PrayerVisibility::Public->value,
            'moderation_status' => PrayerModerationStatus::Pending->value,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$pendingIntention->public_id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_join_rejected_intention(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $rejectedIntention = PrayerIntention::create([
            'user_id' => $author->id,
            'title' => 'Bị từ chối',
            'body' => 'Lời nguyện vi phạm quy chuẩn.',
            'visibility' => PrayerVisibility::Public->value,
            'moderation_status' => PrayerModerationStatus::Rejected->value,
            'rejected_at' => now(),
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$rejectedIntention->public_id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_join_withdrawn_intention(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);
        $intention->update(['withdrawn_at' => now()]);

        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_join_soft_deleted_intention(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);
        $intention->delete();

        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(404);
    }

    // ==========================================
    // 2. IDEMPOTENCY & WITHDRAWAL TESTS
    // ==========================================

    public function test_repeated_put_calls_are_idempotent(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        // Ten successive PUT calls
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");
            $response->assertStatus(200)
                ->assertJsonPath('data.is_participating', true)
                ->assertJsonPath('data.participation_count', 1);
        }

        $this->assertDatabaseCount('prayer_participations', 1);
    }

    public function test_delete_withdraws_participation_idempotently(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        // Join first
        $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        // First withdraw
        $response1 = $this->actingAs($user)->deleteJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        $response1->assertStatus(200)
            ->assertJsonPath('data.is_participating', false)
            ->assertJsonPath('data.participation_count', 0);

        // Second withdraw
        $response2 = $this->actingAs($user)->deleteJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        $response2->assertStatus(200)
            ->assertJsonPath('data.is_participating', false)
            ->assertJsonPath('data.participation_count', 0);

        // Participation count never negative
        $this->assertGreaterThanOrEqual(0, $response2->json('data.participation_count'));

        $this->assertDatabaseHas('prayer_participations', [
            'prayer_intention_id' => $intention->id,
            'user_id' => $user->id,
        ]);
        $participation = PrayerParticipation::first();
        $this->assertNotNull($participation->withdrawn_at);
    }

    public function test_rejoin_restores_active_participation(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        // 1. Join
        $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        // 2. Withdraw
        $this->actingAs($user)->deleteJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        // 3. Rejoin
        $response = $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_participating', true)
            ->assertJsonPath('data.participation_count', 1);

        $this->assertDatabaseCount('prayer_participations', 1);
        $participation = PrayerParticipation::first();
        $this->assertNull($participation->withdrawn_at);
    }

    // ==========================================
    // 3. COUNT & BATCH STATUS TESTS
    // ==========================================

    public function test_multiple_users_participation_counting(): void
    {
        $author = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        // Initial count 0
        $feed = $this->getJson('/api/v1/prayer-intentions');
        $feed->assertStatus(200)->assertJsonPath('data.0.participation_count', 0);

        // User A joins -> count = 1
        $this->actingAs($userA)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        // User B joins -> count = 2
        $this->actingAs($userB)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        // User C joins -> count = 3
        $this->actingAs($userC)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $detail = $this->getJson("/api/v1/prayer-intentions/{$intention->public_id}");
        $detail->assertStatus(200)->assertJsonPath('data.participation_count', 3);

        // User B withdraws -> count = 2
        $this->actingAs($userB)->deleteJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $detailAfter = $this->getJson("/api/v1/prayer-intentions/{$intention->public_id}");
        $detailAfter->assertStatus(200)->assertJsonPath('data.participation_count', 2);
    }

    public function test_batch_viewer_status_endpoint(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();

        $intention1 = $this->createPublicApprovedIntention($author);
        $intention2 = $this->createPublicApprovedIntention($author);
        $intention3 = $this->createPublicApprovedIntention($author);

        // User joins intention 1 and 3
        $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention1->public_id}");
        $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention3->public_id}");

        // Query status for all 3
        $ids = "{$intention1->public_id},{$intention2->public_id},{$intention3->public_id}";
        $response = $this->actingAs($user)->getJson("/api/v1/me/prayer-participations/status?public_ids={$ids}");

        $response->assertStatus(200)
            ->assertJsonPath("data.statuses.{$intention1->public_id}", true)
            ->assertJsonPath("data.statuses.{$intention2->public_id}", false)
            ->assertJsonPath("data.statuses.{$intention3->public_id}", true);
    }

    // ==========================================
    // 4. PERSONAL PARTICIPATION HISTORY & PRIVACY
    // ==========================================

    public function test_personal_participation_history_shows_only_own_active_public_items(): void
    {
        $author = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $intention1 = $this->createPublicApprovedIntention($author);
        $intention2 = $this->createPublicApprovedIntention($author);

        // User A joins intention 1
        $this->actingAs($userA)->putJson("/api/v1/me/prayer-participations/{$intention1->public_id}");

        // User B joins intention 2
        $this->actingAs($userB)->putJson("/api/v1/me/prayer-participations/{$intention2->public_id}");

        // User A views personal history -> only intention 1
        $response = $this->actingAs($userA)->getJson('/api/v1/me/prayer-participations');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.prayer_intention.public_id', $intention1->public_id)
            ->assertJsonPath('data.0.prayer_intention.display_author_label', 'Một Phật tử');

        // Strictly verify NO user ID, NO user email, NO avatar exposed
        $json = $response->json();
        $this->assertArrayNotHasKey('user_id', $json['data'][0]['prayer_intention']);
        $this->assertArrayNotHasKey('email', $json['data'][0]['prayer_intention']);
    }

    public function test_participation_does_not_create_practice_session(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        $this->actingAs($user)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        $this->assertEquals(0, PracticeSession::count());
    }

    public function test_user_cannot_withdraw_another_users_participation(): void
    {
        $author = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        // User A participates
        $this->actingAs($userA)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        // User B calls DELETE on that intention
        $response = $this->actingAs($userB)->deleteJson("/api/v1/me/prayer-participations/{$intention->public_id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.is_participating', false)
            ->assertJsonPath('data.participation_count', 1);

        // User A's participation remains active
        $userAParticipation = PrayerParticipation::where('prayer_intention_id', $intention->id)
            ->where('user_id', $userA->id)
            ->first();
        $this->assertNotNull($userAParticipation);
        $this->assertNull($userAParticipation->withdrawn_at);
    }

    public function test_withdrawn_intention_by_author_is_excluded_from_participated_history(): void
    {
        $author = User::factory()->create();
        $userA = User::factory()->create();
        $intention = $this->createPublicApprovedIntention($author);

        $this->actingAs($userA)->putJson("/api/v1/me/prayer-participations/{$intention->public_id}");

        // Author withdraws prayer intention
        $intention->update(['withdrawn_at' => now()]);

        // User A checks history: non-public intention is strictly filtered out
        $response = $this->actingAs($userA)->getJson('/api/v1/me/prayer-participations');
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_batch_viewer_status_with_unrelated_ids_returns_false(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/v1/me/prayer-participations/status?public_ids=non_existent_1,non_existent_2');

        $response->assertStatus(200)
            ->assertJsonPath('data.statuses.non_existent_1', false)
            ->assertJsonPath('data.statuses.non_existent_2', false);
    }
}

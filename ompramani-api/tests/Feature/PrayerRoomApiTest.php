<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerVisibility;
use App\Filament\Resources\PrayerIntentions\PrayerIntentionResource;
use App\Filament\Resources\PrayerResponses\PrayerResponseResource;
use App\Models\PracticeSession;
use App\Models\PrayerIntention;
use App\Models\PrayerParticipation;
use App\Models\PrayerResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerRoomApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'Nguyễn Văn A',
            'email' => 'usera@example.com',
            'role' => 'user',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'Trần Thị B',
            'email' => 'userb@example.com',
            'role' => 'user',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Quản Trị',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_guest_can_read_public_messages(): void
    {
        // Create an approved public prayer
        $prayer = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Nguyện cầu thế giới hòa bình, nhân dân an lạc.',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subMinutes(10),
        ]);

        $response = $this->getJson('/api/v1/prayer-room/messages');

        $response->assertOk()
            ->assertJsonPath('data.0.public_id', $prayer->public_id)
            ->assertJsonPath('data.0.body', 'Nguyện cầu thế giới hòa bình, nhân dân an lạc.')
            ->assertJsonPath('data.0.display_name', 'Một Phật tử')
            ->assertJsonPath('data.0.is_own', false);

        // Verify NO PII leaked
        $jsonString = $response->getContent();
        $this->assertStringNotContainsString('usera@example.com', $jsonString);
        $this->assertStringNotContainsString('Nguyễn Văn A', $jsonString);
    }

    public function test_guest_cannot_send_message(): void
    {
        $response = $this->postJson('/api/v1/prayer-room/messages', [
            'body' => 'Lời nguyện của khách',
            'consent_public_sharing' => true,
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_send_message_with_consent(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', [
            'body' => 'Nguyện cầu cho gia đình mạnh khỏe bình an.',
            'consent_public_sharing' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'Nguyện cầu cho gia đình mạnh khỏe bình an.')
            ->assertJsonPath('data.is_own', true)
            ->assertJsonPath('data.moderation_status', 'pending');

        $this->assertDatabaseHas('prayer_intentions', [
            'user_id' => $this->userA->id,
            'body' => 'Nguyện cầu cho gia đình mạnh khỏe bình an.',
            'visibility' => 'public',
            'moderation_status' => 'pending',
        ]);
    }

    public function test_sending_without_consent_is_rejected(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', [
            'body' => 'Lời nguyện không có consent',
            'consent_public_sharing' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['consent_public_sharing']);
    }

    public function test_empty_or_too_short_message_is_rejected(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', [
            'body' => ' ',
            'consent_public_sharing' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_oversized_message_is_rejected(): void
    {
        $longBody = str_repeat('Nam Mô A Di Đà Phật ', 70); // > 1000 chars

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', [
            'body' => $longBody,
            'consent_public_sharing' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_duplicate_submission_within_30s_returns_existing_without_duplication(): void
    {
        $payload = [
            'body' => 'Nam Mô Đại Bi Quán Thế Âm Bồ Tát',
            'consent_public_sharing' => true,
        ];

        $res1 = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', $payload);
        $res1->assertCreated();

        $res2 = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', $payload);
        $res2->assertCreated();

        $this->assertEquals($res1->json('data.public_id'), $res2->json('data.public_id'));
        $this->assertEquals(1, PrayerIntention::where('user_id', $this->userA->id)->count());
    }

    public function test_private_prayers_are_never_exposed_in_prayer_room(): void
    {
        // Create private prayer
        $privatePrayer = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện tuyệt mật của User A',
            'visibility' => PrayerVisibility::Private,
            'moderation_status' => PrayerModerationStatus::NotRequired,
        ]);

        // Guest check
        $guestRes = $this->getJson('/api/v1/prayer-room/messages');
        $this->assertStringNotContainsString('Lời nguyện tuyệt mật', $guestRes->getContent());

        // User B check
        $userBRes = $this->actingAs($this->userB, 'sanctum')->getJson('/api/v1/prayer-room/messages');
        $this->assertStringNotContainsString('Lời nguyện tuyệt mật', $userBRes->getContent());

        // Even User A (owner) does not see private prayers inside the public chat room
        $userARes = $this->actingAs($this->userA, 'sanctum')->getJson('/api/v1/prayer-room/messages');
        $this->assertStringNotContainsString('Lời nguyện tuyệt mật', $userARes->getContent());
    }

    public function test_withdrawn_and_rejected_prayers_are_not_exposed(): void
    {
        PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện đã bị từ chối',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Rejected,
        ]);

        PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện đã bị rút lại',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subHour(),
            'withdrawn_at' => now()->subMinute(),
        ]);

        $res = $this->actingAs($this->userB, 'sanctum')->getJson('/api/v1/prayer-room/messages');
        $this->assertStringNotContainsString('Lời nguyện đã bị từ chối', $res->getContent());
        $this->assertStringNotContainsString('Lời nguyện đã bị rút lại', $res->getContent());
    }

    public function test_user_can_reply_to_public_prayer_and_it_creates_response(): void
    {
        $parentPrayer = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Cầu cho mẹ con phẫu thuật thành công.',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->userB, 'sanctum')->postJson('/api/v1/prayer-room/messages', [
            'body' => 'Nguyện mười phương chư Phật gia hộ cho bác bình an mau khỏe!',
            'consent_public_sharing' => true,
            'reply_to_public_id' => $parentPrayer->public_id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'response')
            ->assertJsonPath('data.moderation_status', 'pending');

        $this->assertDatabaseHas('prayer_responses', [
            'prayer_intention_id' => $parentPrayer->id,
            'user_id' => $this->userB->id,
            'body' => 'Nguyện mười phương chư Phật gia hộ cho bác bình an mau khỏe!',
            'moderation_status' => 'pending',
        ]);
    }

    public function test_user_cannot_delete_others_message(): void
    {
        $prayer = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện của A',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subHour(),
        ]);

        // User B attempts to delete A's prayer
        $response = $this->actingAs($this->userB, 'sanctum')->deleteJson("/api/v1/prayer-room/messages/{$prayer->public_id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('prayer_intentions', [
            'id' => $prayer->id,
            'withdrawn_at' => null,
            'deleted_at' => null,
        ]);
    }

    public function test_owner_and_admin_can_delete_message(): void
    {
        $prayer1 = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện của A xóa bởi chính A',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subHour(),
        ]);

        $res1 = $this->actingAs($this->userA, 'sanctum')->deleteJson("/api/v1/prayer-room/messages/{$prayer1->public_id}");
        $res1->assertOk();
        $this->assertSoftDeleted('prayer_intentions', ['id' => $prayer1->id]);

        $prayer2 = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện của A xóa bởi Admin',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subHour(),
        ]);

        $res2 = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/prayer-room/messages/{$prayer2->public_id}");
        $res2->assertOk();
        $this->assertSoftDeleted('prayer_intentions', ['id' => $prayer2->id]);
    }

    public function test_reporting_message_works_and_prevents_self_reporting(): void
    {
        $prayer = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'body' => 'Lời nguyện bị report vì nội dung quảng cáo',
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subHour(),
        ]);

        // Self report rejected
        $selfRes = $this->actingAs($this->userA, 'sanctum')->postJson("/api/v1/prayer-room/messages/{$prayer->public_id}/report", [
            'reason' => 'spam',
            'details' => 'Tự report',
        ]);
        $selfRes->assertStatus(422);

        // User B report success
        $res = $this->actingAs($this->userB, 'sanctum')->postJson("/api/v1/prayer-room/messages/{$prayer->public_id}/report", [
            'reason' => 'spam',
            'details' => 'Tin nhắn rao bán sản phẩm không phù hợp',
        ]);
        $res->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('prayer_intention_reports', [
            'prayer_intention_id' => $prayer->id,
            'reporter_user_id' => $this->userB->id,
            'reason' => 'spam',
        ]);
    }

    public function test_no_practice_session_or_streak_is_modified(): void
    {
        $sessionCountBefore = PracticeSession::count();

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/prayer-room/messages', [
            'body' => 'Cầu nguyện thuần túy không tạo practice session',
            'consent_public_sharing' => true,
        ]);

        $this->assertEquals($sessionCountBefore, PracticeSession::count());
    }

    public function test_filament_navigation_has_only_phong_loi_nguyen(): void
    {
        // PrayerIntentionResource label
        $this->assertEquals('Phòng Lời Nguyện', PrayerIntentionResource::getNavigationLabel());
        $this->assertEquals('Phòng Lời Nguyện', PrayerIntentionResource::getPluralModelLabel());

        // PrayerResponseResource should not register in navigation
        $this->assertFalse(PrayerResponseResource::shouldRegisterNavigation());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerNotificationType;
use App\Enums\PrayerRejectionReason;
use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerVisibility;
use App\Models\PrayerIntention;
use App\Models\PrayerParticipation;
use App\Models\PrayerResponse;
use App\Models\User;
use App\Services\Notification\PrayerNotificationService;
use App\Services\Prayer\PrayerModerationService;
use App\Services\Prayer\PrayerResponseModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PrayerNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $userA;

    protected User $userB;

    protected PrayerModerationService $prayerModService;

    protected PrayerResponseModerationService $responseModService;

    protected PrayerNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->userA = User::factory()->create(['role' => 'user']);
        $this->userB = User::factory()->create(['role' => 'user']);

        $this->prayerModService = app(PrayerModerationService::class);
        $this->responseModService = app(PrayerResponseModerationService::class);
        $this->notificationService = app(PrayerNotificationService::class);
    }

    // ==========================================
    // DELIVERY & EVENT TRIGGER TESTS
    // ==========================================

    public function test_approved_prayer_intention_creates_owner_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);

        $this->prayerModService->approve($this->admin, $intention);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->userA->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $this->userA->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame(PrayerNotificationType::PrayerIntentionApproved->value, $notification->data['type']);
        $this->assertSame('moderation', $notification->data['category']);
        $this->assertStringContainsString('đã được duyệt', $notification->data['message']);
        $this->assertNull($notification->read_at);
        // Verify payload does NOT leak user body
        $this->assertArrayNotHasKey('body', $notification->data);
    }

    public function test_rejected_prayer_intention_creates_owner_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);

        $this->prayerModService->reject(
            $this->admin,
            $intention,
            PrayerRejectionReason::Harassment->value,
            'Nội dung chưa phù hợp'
        );

        $notification = $this->userA->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame(PrayerNotificationType::PrayerIntentionRejected->value, $notification->data['type']);
        $this->assertStringContainsString('chưa được chấp thuận', $notification->data['message']);
    }

    public function test_revoked_prayer_intention_creates_owner_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $this->prayerModService->revoke($this->admin, $intention, 'Admin revoke');

        $notification = $this->userA->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame(PrayerNotificationType::PrayerIntentionRevoked->value, $notification->data['type']);
        $this->assertStringContainsString('ngừng hiển thị', $notification->data['message']);
    }

    public function test_approved_prayer_response_creates_author_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $response = PrayerResponse::factory()->create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $this->userB->id,
            'moderation_status' => PrayerResponseModerationStatus::Pending,
        ]);

        $this->responseModService->approve($this->admin, $response);

        // Author (User B) receives approval notification
        $authorNotice = $this->userB->notifications()->first();
        $this->assertNotNull($authorNotice);
        $this->assertSame(PrayerNotificationType::PrayerResponseApproved->value, $authorNotice->data['type']);
        $this->assertStringContainsString('đã được duyệt', $authorNotice->data['message']);

        // Parent owner (User A) has new_response_updates_enabled = false by default, so User A should NOT get notice yet
        $this->assertSame(0, $this->userA->notifications()->count());
    }

    public function test_approved_prayer_response_notifies_parent_owner_when_opted_in(): void
    {
        // Enable new_response_updates_enabled for User A
        $pref = $this->userA->getEffectiveNotificationPreferences();
        $pref->update(['new_response_updates_enabled' => true]);

        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $response = PrayerResponse::factory()->create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $this->userB->id,
            'moderation_status' => PrayerResponseModerationStatus::Pending,
        ]);

        $this->responseModService->approve($this->admin, $response);

        // User A receives community notice
        $parentNotice = $this->userA->notifications()->first();
        $this->assertNotNull($parentNotice);
        $this->assertSame(PrayerNotificationType::PrayerNewApprovedResponse->value, $parentNotice->data['type']);
        $this->assertSame('community', $parentNotice->data['category']);
        $this->assertStringContainsString('Lời nhắn mới', $parentNotice->data['title']);

        // No author identity leaked in notification data
        $this->assertArrayNotHasKey('author_id', $parentNotice->data);
        $this->assertArrayNotHasKey('user_id', $parentNotice->data);
    }

    public function test_same_user_as_parent_owner_and_author_does_not_receive_duplicate_notice(): void
    {
        // User A writes response on own prayer, with new_response_updates_enabled = true
        $pref = $this->userA->getEffectiveNotificationPreferences();
        $pref->update(['new_response_updates_enabled' => true]);

        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $response = PrayerResponse::factory()->create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $this->userA->id, // Same user
            'moderation_status' => PrayerResponseModerationStatus::Pending,
        ]);

        $this->responseModService->approve($this->admin, $response);

        // Should only have 1 notification (author approval notice), NOT a second "new response" notice to self
        $this->assertSame(1, $this->userA->notifications()->count());
        $notice = $this->userA->notifications()->first();
        $this->assertSame(PrayerNotificationType::PrayerResponseApproved->value, $notice->data['type']);
    }

    public function test_rejected_prayer_response_creates_author_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $response = PrayerResponse::factory()->create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $this->userB->id,
            'moderation_status' => PrayerResponseModerationStatus::Pending,
        ]);

        $this->responseModService->reject($this->admin, $response, 'spam', 'Nội dung spam');

        $notice = $this->userB->notifications()->first();
        $this->assertNotNull($notice);
        $this->assertSame(PrayerNotificationType::PrayerResponseRejected->value, $notice->data['type']);
        $this->assertStringContainsString('chưa được chấp thuận', $notice->data['message']);
    }

    public function test_revoked_prayer_response_creates_author_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $response = PrayerResponse::factory()->create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $this->userB->id,
            'moderation_status' => PrayerResponseModerationStatus::Approved,
            'published_at' => now(),
        ]);

        $this->responseModService->revoke($this->admin, $response, 'unsafe');

        $notice = $this->userB->notifications()->first();
        $this->assertNotNull($notice);
        $this->assertSame(PrayerNotificationType::PrayerResponseRevoked->value, $notice->data['type']);
    }

    public function test_repeated_event_handling_does_not_duplicate_notification(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now(),
        ]);

        // First notification
        $this->notificationService->notifyPrayerIntentionApproved($intention);
        $this->assertSame(1, $this->userA->notifications()->count());

        // Repeated trigger with identical state
        $res = $this->notificationService->notifyPrayerIntentionApproved($intention);
        $this->assertFalse($res);
        $this->assertSame(1, $this->userA->notifications()->count());
    }

    // ==========================================
    // PREFERENCES TESTS
    // ==========================================

    public function test_new_user_gets_default_preferences(): void
    {
        $res = $this->actingAs($this->userA)->getJson('/api/v1/me/notification-preferences');

        $res->assertOk()
            ->assertJson([
                'data' => [
                    'moderation_updates_enabled' => true,
                    'new_response_updates_enabled' => false,
                    'participation_digest_enabled' => false,
                ],
            ]);
    }

    public function test_user_can_update_preferences(): void
    {
        $res = $this->actingAs($this->userA)->putJson('/api/v1/me/notification-preferences', [
            'moderation_updates_enabled' => false,
            'new_response_updates_enabled' => true,
            'participation_digest_enabled' => true,
        ]);

        $res->assertOk()
            ->assertJson([
                'data' => [
                    'moderation_updates_enabled' => false,
                    'new_response_updates_enabled' => true,
                    'participation_digest_enabled' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $this->userA->id,
            'moderation_updates_enabled' => false,
            'new_response_updates_enabled' => true,
            'participation_digest_enabled' => true,
        ]);
    }

    public function test_disabled_moderation_preference_suppresses_future_notice(): void
    {
        $this->userA->getEffectiveNotificationPreferences()->update([
            'moderation_updates_enabled' => false,
        ]);

        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);

        $this->prayerModService->approve($this->admin, $intention);

        $this->assertSame(0, $this->userA->notifications()->count());
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    public function test_guest_cannot_access_inbox_or_unread_count(): void
    {
        $this->getJson('/api/v1/me/notifications')->assertUnauthorized();
        $this->getJson('/api/v1/me/notifications/unread-count')->assertUnauthorized();
        $this->getJson('/api/v1/me/notification-preferences')->assertUnauthorized();
    }

    public function test_user_a_cannot_access_or_modify_user_b_notifications(): void
    {
        // Give User B a notification
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userB->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);
        $this->prayerModService->approve($this->admin, $intention);

        $noticeB = $this->userB->notifications()->firstOrFail();

        // User A tries to read it
        $this->actingAs($this->userA)
            ->patchJson("/api/v1/me/notifications/{$noticeB->id}/read")
            ->assertNotFound();

        // User A tries to delete it
        $this->actingAs($this->userA)
            ->deleteJson("/api/v1/me/notifications/{$noticeB->id}")
            ->assertNotFound();

        // Ensure User B's notification is still unread and intact
        $this->assertNull($noticeB->fresh()->read_at);
    }

    // ==========================================
    // READ STATE & UNREAD COUNT TESTS
    // ==========================================

    public function test_mark_as_read_and_unread_count(): void
    {
        // Create 2 notifications for User A
        $intention1 = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);
        $this->prayerModService->approve($this->admin, $intention1);

        $intention2 = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);
        $this->prayerModService->reject($this->admin, $intention2, 'other');

        // Check unread count
        $countRes = $this->actingAs($this->userA)->getJson('/api/v1/me/notifications/unread-count');
        $countRes->assertOk()->assertJson(['data' => ['unread_count' => 2]]);

        // Mark first notification as read
        $firstNotice = $this->userA->notifications()->first();
        $readRes = $this->actingAs($this->userA)->patchJson("/api/v1/me/notifications/{$firstNotice->id}/read");
        $readRes->assertOk()->assertJson([
            'data' => [
                'id' => $firstNotice->id,
                'is_read' => true,
            ],
        ]);

        // Unread count is now 1
        $countRes2 = $this->actingAs($this->userA)->getJson('/api/v1/me/notifications/unread-count');
        $countRes2->assertOk()->assertJson(['data' => ['unread_count' => 1]]);

        // Mark all as read
        $markAllRes = $this->actingAs($this->userA)->postJson('/api/v1/me/notifications/read-all');
        $markAllRes->assertOk();

        // Unread count is now 0
        $countRes3 = $this->actingAs($this->userA)->getJson('/api/v1/me/notifications/unread-count');
        $countRes3->assertOk()->assertJson(['data' => ['unread_count' => 0]]);
    }

    public function test_dismiss_notification_removes_it_from_inbox(): void
    {
        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Pending,
        ]);
        $this->prayerModService->approve($this->admin, $intention);

        $notice = $this->userA->notifications()->firstOrFail();

        $delRes = $this->actingAs($this->userA)->deleteJson("/api/v1/me/notifications/{$notice->id}");
        $delRes->assertOk();

        $this->assertDatabaseMissing('notifications', ['id' => $notice->id]);

        // Underlying intention must NOT be deleted
        $this->assertDatabaseHas('prayer_intentions', ['id' => $intention->id]);
    }

    // ==========================================
    // PARTICIPATION DIGEST COMMAND TESTS
    // ==========================================

    public function test_participation_digest_command(): void
    {
        // 1. Opt in User A
        $this->userA->getEffectiveNotificationPreferences()->update([
            'participation_digest_enabled' => true,
        ]);

        $intention = PrayerIntention::factory()->create([
            'user_id' => $this->userA->id,
            'visibility' => PrayerVisibility::Public,
            'moderation_status' => PrayerModerationStatus::Approved,
            'published_at' => now()->subDays(2),
        ]);

        // User B joins yesterday
        $yesterday = Carbon::yesterday()->setHour(10);
        PrayerParticipation::create([
            'prayer_intention_id' => $intention->id,
            'user_id' => $this->userB->id,
            'participated_at' => $yesterday,
        ]);

        // Run artisan command
        $this->artisan('notifications:send-prayer-digests')
            ->expectsOutputToContain('Đã gửi 1 thông báo tổng hợp thành công')
            ->assertSuccessful();

        // Verify digest notification
        $digest = $this->userA->notifications()
            ->where('data', 'like', '%"type":"prayer_participation_digest"%')
            ->first();

        $this->assertNotNull($digest);
        $this->assertSame('community', $digest->data['category']);
        $this->assertStringContainsString('1 lượt đồng hành', $digest->data['message']);

        // Rerunning command for same date is idempotent (0 sent)
        $this->artisan('notifications:send-prayer-digests')
            ->expectsOutputToContain('Đã gửi 0 thông báo tổng hợp thành công')
            ->assertSuccessful();
    }
}

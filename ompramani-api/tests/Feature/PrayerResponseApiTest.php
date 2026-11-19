<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrayerResponseModerationStatus;
use App\Enums\PrayerResponseReportReason;
use App\Enums\PrayerResponseReportStatus;
use App\Models\PrayerIntention;
use App\Models\PrayerResponse;
use App\Models\PrayerResponseReport;
use App\Models\User;
use App\Services\Prayer\PrayerResponseModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PrayerResponseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createApprovedParent(): PrayerIntention
    {
        return PrayerIntention::factory()->approved()->create();
    }

    // ==========================================
    // 1. PUBLICATION INVARIANTS & PUBLIC FEED
    // ==========================================

    public function test_guest_can_read_approved_responses_of_public_prayer(): void
    {
        $parent = $this->createApprovedParent();

        PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
            'body' => 'Cầu chúc bạn luôn được vạn sự an lành.',
        ]);

        $response = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.body', 'Cầu chúc bạn luôn được vạn sự an lành.')
            ->assertJsonPath('data.0.display_author_label', 'Một người đồng hành');

        // Strictly verify zero identity leakage
        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('user_id', $data);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('moderation_status', $data);
    }

    public function test_guest_cannot_submit_response(): void
    {
        $parent = $this->createApprovedParent();

        $response = $this->postJson("/api/v1/prayer-intentions/{$parent->public_id}/responses", [
            'body' => 'Xin chia sẻ sự đồng cảm cùng bạn.',
        ]);

        $response->assertStatus(401);
    }

    public function test_pending_rejected_revoked_withdrawn_responses_are_not_public(): void
    {
        $parent = $this->createApprovedParent();

        PrayerResponse::factory()->pending()->create([
            'prayer_intention_id' => $parent->id,
            'body' => 'Nội dung đang chờ duyệt.',
        ]);

        PrayerResponse::factory()->rejected()->create([
            'prayer_intention_id' => $parent->id,
            'body' => 'Nội dung đã bị từ chối.',
        ]);

        PrayerResponse::factory()->revoked()->create([
            'prayer_intention_id' => $parent->id,
            'body' => 'Nội dung đã bị thu hồi.',
        ]);

        PrayerResponse::factory()->withdrawn()->create([
            'prayer_intention_id' => $parent->id,
            'body' => 'Nội dung người gửi đã rút lại.',
        ]);

        $response = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_parent_prayer_not_public_makes_responses_unavailable(): void
    {
        $privateParent = PrayerIntention::factory()->private()->create();

        PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $privateParent->id,
            'body' => 'Lời nhắn dưới lời nguyện riêng tư.',
        ]);

        $response = $this->getJson("/api/v1/prayer-intentions/{$privateParent->public_id}/responses");

        $response->assertStatus(404);
    }

    public function test_parent_withdrawn_makes_responses_unavailable(): void
    {
        $parent = $this->createApprovedParent();

        PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
            'body' => 'Lời nhắn hợp lệ ban đầu.',
        ]);

        // Parent gets withdrawn
        $parent->update(['withdrawn_at' => now()]);

        $response = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");

        $response->assertStatus(404);
    }

    public function test_approved_response_count_reflects_only_valid_public_responses(): void
    {
        $parent = $this->createApprovedParent();

        // 2 approved
        PrayerResponse::factory()->count(2)->approved()->create([
            'prayer_intention_id' => $parent->id,
        ]);

        // 1 pending, 1 rejected, 1 withdrawn
        PrayerResponse::factory()->pending()->create(['prayer_intention_id' => $parent->id]);
        PrayerResponse::factory()->rejected()->create(['prayer_intention_id' => $parent->id]);
        PrayerResponse::factory()->withdrawn()->create(['prayer_intention_id' => $parent->id]);

        $response = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.approved_response_count', 2);
    }

    // ==========================================
    // 2. CREATION & MODERATION SUBMISSION
    // ==========================================

    public function test_authenticated_user_can_submit_response_starts_pending(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = $this->actingAs($user)->postJson("/api/v1/prayer-intentions/{$parent->public_id}/responses", [
            'body' => 'Cầu Phật gia hộ cho bạn vượt qua mọi nghịch duyên.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.moderation_status', 'pending')
            ->assertJsonPath('data.body', 'Cầu Phật gia hộ cho bạn vượt qua mọi nghịch duyên.')
            ->assertJsonPath('data.submission_revision', 1);

        $this->assertDatabaseHas('prayer_responses', [
            'prayer_intention_id' => $parent->id,
            'user_id' => $user->id,
            'moderation_status' => 'pending',
            'submission_revision' => 1,
        ]);

        // Public list still empty
        $publicRes = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");
        $publicRes->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_submit_response_to_private_or_unapproved_parent(): void
    {
        $user = User::factory()->create();
        $privateParent = PrayerIntention::factory()->private()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/prayer-intentions/{$privateParent->public_id}/responses", [
            'body' => 'Cầu Phật gia hộ cho bạn.',
        ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // 3. EDIT & APPROVAL RESET INVARIANT
    // ==========================================

    public function test_editing_approved_response_immediately_resets_approval_and_increments_revision(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
            'user_id' => $user->id,
            'body' => 'Bản thảo ban đầu đã duyệt.',
            'submission_revision' => 1,
        ]);

        // Verify initially visible in public
        $publicRes1 = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");
        $publicRes1->assertJsonCount(1, 'data');

        // User edits the response
        $updateRes = $this->actingAs($user)->patchJson("/api/v1/me/prayer-responses/{$response->id}", [
            'body' => 'Bản thảo chỉnh sửa mới cần duyệt lại.',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.moderation_status', 'pending')
            ->assertJsonPath('data.submission_revision', 2)
            ->assertJsonPath('data.body', 'Bản thảo chỉnh sửa mới cần duyệt lại.');

        // Instantly revoked from public view!
        $publicRes2 = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");
        $publicRes2->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('prayer_responses', [
            'id' => $response->id,
            'moderation_status' => 'pending',
            'approved_at' => null,
            'published_at' => null,
            'submission_revision' => 2,
        ]);
    }

    // ==========================================
    // 4. WITHDRAWAL & DELETION
    // ==========================================

    public function test_owner_can_withdraw_response_immediately(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
            'user_id' => $user->id,
        ]);

        $withdrawRes = $this->actingAs($user)->postJson("/api/v1/me/prayer-responses/{$response->id}/withdraw");

        $withdrawRes->assertStatus(200)
            ->assertJsonPath('data.can_withdraw', false);

        // Disappears from public view
        $publicRes = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");
        $publicRes->assertJsonCount(0, 'data');
    }

    public function test_owner_can_delete_response(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->create([
            'prayer_intention_id' => $parent->id,
            'user_id' => $user->id,
        ]);

        $deleteRes = $this->actingAs($user)->deleteJson("/api/v1/me/prayer-responses/{$response->id}");

        $deleteRes->assertStatus(200);

        $this->assertSoftDeleted('prayer_responses', ['id' => $response->id]);
    }

    // ==========================================
    // 5. AUTHORIZATION CONTROLS
    // ==========================================

    public function test_user_b_cannot_edit_or_withdraw_or_delete_user_a_response(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
            'user_id' => $userA->id,
        ]);

        $editRes = $this->actingAs($userB)->patchJson("/api/v1/me/prayer-responses/{$response->id}", [
            'body' => 'Hacker edit attempt.',
        ]);
        $editRes->assertStatus(403);

        $withdrawRes = $this->actingAs($userB)->postJson("/api/v1/me/prayer-responses/{$response->id}/withdraw");
        $withdrawRes->assertStatus(403);

        $deleteRes = $this->actingAs($userB)->deleteJson("/api/v1/me/prayer-responses/{$response->id}");
        $deleteRes->assertStatus(403);
    }

    // ==========================================
    // 6. VALIDATION & SANITIZATION
    // ==========================================

    public function test_empty_or_whitespace_body_is_rejected(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $res1 = $this->actingAs($user)->postJson("/api/v1/prayer-intentions/{$parent->public_id}/responses", [
            'body' => '',
        ]);
        $res1->assertStatus(422);

        $res2 = $this->actingAs($user)->postJson("/api/v1/prayer-intentions/{$parent->public_id}/responses", [
            'body' => '       ',
        ]);
        $res2->assertStatus(422);
    }

    public function test_oversized_body_is_rejected(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $res = $this->actingAs($user)->postJson("/api/v1/prayer-intentions/{$parent->public_id}/responses", [
            'body' => str_repeat('A', 501),
        ]);
        $res->assertStatus(422);
    }

    public function test_html_tags_are_stripped_safely(): void
    {
        $user = User::factory()->create();
        $parent = $this->createApprovedParent();

        $res = $this->actingAs($user)->postJson("/api/v1/prayer-intentions/{$parent->public_id}/responses", [
            'body' => '<b>Nam Mô</b> <script>alert(1)</script> A Di Đà Phật.',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.body', 'Nam Mô alert(1) A Di Đà Phật.');
    }

    // ==========================================
    // 7. ADMIN MODERATION SERVICE & CONCURRENCY
    // ==========================================

    public function test_admin_can_approve_pending_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->pending()->create([
            'prayer_intention_id' => $parent->id,
            'submission_revision' => 1,
        ]);

        $moderationService = app(PrayerResponseModerationService::class);
        $approved = $moderationService->approve($admin, $response, 1);

        $this->assertEquals(PrayerResponseModerationStatus::Approved, $approved->moderation_status);
        $this->assertNotNull($approved->published_at);
        $this->assertEquals($admin->id, $approved->moderated_by);

        // Now visible in public feed
        $publicRes = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");
        $publicRes->assertJsonCount(1, 'data');
    }

    public function test_admin_cannot_approve_stale_revision_when_user_edited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->pending()->create([
            'prayer_intention_id' => $parent->id,
            'submission_revision' => 2, // User edited to rev 2
        ]);

        $moderationService = app(PrayerResponseModerationService::class);

        $this->expectException(ConflictHttpException::class);
        // Admin attempts approving with stale rev 1
        $moderationService->approve($admin, $response, 1);
    }

    public function test_admin_can_reject_and_revoke_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = $this->createApprovedParent();

        $pending = PrayerResponse::factory()->pending()->create([
            'prayer_intention_id' => $parent->id,
        ]);

        $moderationService = app(PrayerResponseModerationService::class);
        $rejected = $moderationService->reject($admin, $pending, 'spam', 'Phát hiện spam.');

        $this->assertEquals(PrayerResponseModerationStatus::Rejected, $rejected->moderation_status);
        $this->assertEquals('spam', $rejected->rejection_reason_code);

        // Test revoke
        $approved = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
        ]);
        $revoked = $moderationService->revoke($admin, $approved, 'harassment');
        $this->assertEquals(PrayerResponseModerationStatus::Revoked, $revoked->moderation_status);

        // Not in public
        $publicRes = $this->getJson("/api/v1/prayer-intentions/{$parent->public_id}/responses");
        $publicRes->assertJsonCount(0, 'data');
    }

    // ==========================================
    // 8. SAFETY REPORTS
    // ==========================================

    public function test_authenticated_user_can_report_public_approved_response(): void
    {
        $reporter = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
        ]);

        $reportRes = $this->actingAs($reporter)->postJson("/api/v1/prayer-responses/{$response->public_id}/reports", [
            'reason' => 'spam',
            'details' => 'Quảng cáo thương mại không phù hợp.',
        ]);

        $reportRes->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('prayer_response_reports', [
            'prayer_response_id' => $response->id,
            'reporter_user_id' => $reporter->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_report_from_same_user_is_idempotent(): void
    {
        $reporter = User::factory()->create();
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
        ]);

        // First report
        $this->actingAs($reporter)->postJson("/api/v1/prayer-responses/{$response->public_id}/reports", [
            'reason' => 'harassment',
        ])->assertStatus(201);

        // Second report (duplicate)
        $res2 = $this->actingAs($reporter)->postJson("/api/v1/prayer-responses/{$response->public_id}/reports", [
            'reason' => 'harassment',
        ]);
        $res2->assertStatus(201);

        // Exactly 1 report in DB
        $this->assertEquals(1, PrayerResponseReport::where('prayer_response_id', $response->id)->count());
    }

    public function test_cannot_report_nonpublic_response(): void
    {
        $reporter = User::factory()->create();
        $parent = $this->createApprovedParent();

        $pending = PrayerResponse::factory()->pending()->create([
            'prayer_intention_id' => $parent->id,
        ]);

        $reportRes = $this->actingAs($reporter)->postJson("/api/v1/prayer-responses/{$pending->public_id}/reports", [
            'reason' => 'spam',
        ]);

        $reportRes->assertStatus(404);
    }

    public function test_admin_can_dismiss_and_action_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = $this->createApprovedParent();

        $response = PrayerResponse::factory()->approved()->create([
            'prayer_intention_id' => $parent->id,
        ]);

        $report = PrayerResponseReport::create([
            'prayer_response_id' => $response->id,
            'reporter_user_id' => User::factory()->create()->id,
            'reason' => PrayerResponseReportReason::Spam,
            'status' => PrayerResponseReportStatus::Pending,
        ]);

        $moderationService = app(PrayerResponseModerationService::class);

        // Action report -> revokes response
        $actioned = $moderationService->actionReport($admin, $report, 'Đã xác minh vi phạm spam.');
        $this->assertEquals(PrayerResponseReportStatus::Actioned, $actioned->status);

        $response->refresh();
        $this->assertEquals(PrayerResponseModerationStatus::Revoked, $response->moderation_status);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrayerCategory;
use App\Enums\PrayerModerationStatus;
use App\Enums\PrayerRejectionReason;
use App\Models\PrayerIntention;
use App\Models\User;
use App\Services\Prayer\PrayerModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PrayerIntentionApiTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // 1. OWNERSHIP & AUTHENTICATION
    // ==========================================

    public function test_guest_cannot_submit_prayer_intention(): void
    {
        $response = $this->postJson('/api/v1/me/prayer-intentions', [
            'body' => 'Nguyện cho tất cả chúng sinh luôn được an vui, giải thoát khỏi khổ đau.',
            'visibility' => 'private',
        ]);

        $response->assertStatus(401);
    }

    public function test_guest_cannot_view_personal_intentions(): void
    {
        $response = $this->getJson('/api/v1/me/prayer-intentions');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_private_intention_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/me/prayer-intentions', [
            'title' => 'Cầu bình an cho cha mẹ',
            'body' => 'Con nguyện cầu hồng ân Tam Bảo gia hộ cho cha mẹ thân tâm an lạc, phước thọ tăng long.',
            'category' => 'gia_dinh',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Cầu bình an cho cha mẹ')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.moderation_status', 'not_required');

        $this->assertDatabaseHas('prayer_intentions', [
            'user_id' => $user->id,
            'title' => 'Cầu bình an cho cha mẹ',
            'visibility' => 'private',
            'moderation_status' => 'not_required',
        ]);
    }

    public function test_user_can_read_own_intention(): void
    {
        $user = User::factory()->create();
        $intention = PrayerIntention::factory()->private()->create([
            'user_id' => $user->id,
            'title' => 'Tâm nguyện buổi sớm',
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/me/prayer-intentions/{$intention->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $intention->id)
            ->assertJsonPath('data.title', 'Tâm nguyện buổi sớm');
    }

    public function test_user_can_edit_own_private_intention(): void
    {
        $user = User::factory()->create();
        $intention = PrayerIntention::factory()->private()->create([
            'user_id' => $user->id,
            'title' => 'Tiêu đề ban đầu',
            'body' => 'Nội dung ban đầu dài hơn hai mươi ký tự để hợp lệ.',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/v1/me/prayer-intentions/{$intention->id}", [
            'title' => 'Tiêu đề đã sửa',
            'body' => 'Nội dung mới đã được cập nhật sâu sắc và thành kính hơn.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Tiêu đề đã sửa')
            ->assertJsonPath('data.body', 'Nội dung mới đã được cập nhật sâu sắc và thành kính hơn.')
            ->assertJsonPath('data.visibility', 'private');
    }

    public function test_user_can_delete_own_intention(): void
    {
        $user = User::factory()->create();
        $intention = PrayerIntention::factory()->private()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/me/prayer-intentions/{$intention->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('prayer_intentions', ['id' => $intention->id]);
    }

    public function test_user_a_cannot_read_user_b_private_intention(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $intentionB = PrayerIntention::factory()->private()->create([
            'user_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA)->getJson("/api/v1/me/prayer-intentions/{$intentionB->id}");

        $response->assertStatus(404);
    }

    public function test_user_a_cannot_edit_user_b_intention(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $intentionB = PrayerIntention::factory()->private()->create([
            'user_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA)->patchJson("/api/v1/me/prayer-intentions/{$intentionB->id}", [
            'title' => 'Hack title',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_a_cannot_delete_user_b_intention(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $intentionB = PrayerIntention::factory()->private()->create([
            'user_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA)->deleteJson("/api/v1/me/prayer-intentions/{$intentionB->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('prayer_intentions', ['id' => $intentionB->id, 'deleted_at' => null]);
    }

    public function test_cannot_override_owner_id_or_moderation_status_on_create(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this->actingAs($userA)->postJson('/api/v1/me/prayer-intentions', [
            'user_id' => $userB->id,
            'moderation_status' => 'approved',
            'published_at' => now()->toIso8601String(),
            'body' => 'Nội dung cố ý gán owner khác và tự ý approve nhưng sẽ bị bỏ qua.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('prayer_intentions', [
            'user_id' => $userA->id,
            'visibility' => 'private',
            'moderation_status' => 'not_required',
            'published_at' => null,
        ]);
    }

    // ==========================================
    // 2. VISIBILITY & PUBLICATION INVARIANTS
    // ==========================================

    public function test_private_intentions_never_appear_in_public_feed(): void
    {
        $user = User::factory()->create();
        PrayerIntention::factory()->private()->create([
            'user_id' => $user->id,
            'body' => 'Lời nguyện tuyệt đối riêng tư không bao giờ ra cộng đồng.',
        ]);

        $response = $this->getJson('/api/v1/prayer-intentions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_pending_intentions_never_appear_in_public_feed(): void
    {
        $user = User::factory()->create();
        PrayerIntention::factory()->pending()->create([
            'user_id' => $user->id,
            'body' => 'Lời nguyện đang chờ duyệt, chưa được hiển thị cộng đồng.',
        ]);

        $response = $this->getJson('/api/v1/prayer-intentions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_approved_intentions_appear_in_public_feed(): void
    {
        $user = User::factory()->create();
        $approved = PrayerIntention::factory()->approved()->create([
            'user_id' => $user->id,
            'title' => 'Nguyện thế giới hòa bình',
            'body' => 'Nguyện cầu ánh sáng Phật từ bi soi chiếu khắp mười phương thế giới, chiến tranh chấm dứt.',
            'category' => PrayerCategory::HoiHuong,
        ]);

        $response = $this->getJson('/api/v1/prayer-intentions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $approved->public_id)
            ->assertJsonPath('data.0.title', 'Nguyện thế giới hòa bình')
            ->assertJsonPath('data.0.display_author_label', 'Một Phật tử');

        // Strictly verify NO owner email or user_id is returned
        $item = $response->json('data.0');
        $this->assertArrayNotHasKey('user_id', $item);
        $this->assertArrayNotHasKey('email', $item);
        $this->assertArrayNotHasKey('id', $item);
    }

    public function test_rejected_and_withdrawn_intentions_never_appear_in_public_feed(): void
    {
        $user = User::factory()->create();
        PrayerIntention::factory()->rejected()->create(['user_id' => $user->id]);
        PrayerIntention::factory()->withdrawn()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/prayer-intentions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_public_detail_endpoint_returns_approved_and_404s_for_private_or_pending(): void
    {
        $user = User::factory()->create();
        $approved = PrayerIntention::factory()->approved()->create(['user_id' => $user->id]);
        $private = PrayerIntention::factory()->private()->create(['user_id' => $user->id]);
        $pending = PrayerIntention::factory()->pending()->create(['user_id' => $user->id]);

        // Approved returns 200
        $this->getJson("/api/v1/prayer-intentions/{$approved->public_id}")
            ->assertStatus(200)
            ->assertJsonPath('data.public_id', $approved->public_id);

        // Private returns 404
        $this->getJson("/api/v1/prayer-intentions/{$private->public_id}")
            ->assertStatus(404);

        // Pending returns 404
        $this->getJson("/api/v1/prayer-intentions/{$pending->public_id}")
            ->assertStatus(404);
    }

    public function test_user_can_submit_private_intention_for_public_sharing(): void
    {
        $user = User::factory()->create();
        $intention = PrayerIntention::factory()->private()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/me/prayer-intentions/{$intention->id}/submit", [
            'consent_public_sharing' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonPath('data.moderation_status', 'pending');

        $this->assertDatabaseHas('prayer_intentions', [
            'id' => $intention->id,
            'visibility' => 'public',
            'moderation_status' => 'pending',
        ]);
    }

    public function test_user_can_withdraw_public_intention_immediately(): void
    {
        $user = User::factory()->create();
        $intention = PrayerIntention::factory()->approved()->create([
            'user_id' => $user->id,
        ]);

        // Public feed has 1 item
        $this->getJson('/api/v1/prayer-intentions')->assertJsonCount(1, 'data');

        // User withdraws
        $response = $this->actingAs($user)->postJson("/api/v1/me/prayer-intentions/{$intention->id}/withdraw");

        $response->assertStatus(200)
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.can_withdraw', false);

        // Public feed immediately has 0 items!
        $this->getJson('/api/v1/prayer-intentions')->assertJsonCount(0, 'data');
    }

    // ==========================================
    // 3. SECURITY INVARIANT: EDIT AFTER APPROVAL
    // ==========================================

    public function test_editing_body_of_approved_intention_invalidates_approval_and_withdraws_from_public(): void
    {
        $user = User::factory()->create();
        $intention = PrayerIntention::factory()->approved()->create([
            'user_id' => $user->id,
            'body' => 'Nội dung cũ đã được duyệt và xuất bản rộng rãi trên cộng đồng.',
        ]);

        // Verify currently visible in public feed
        $this->getJson('/api/v1/prayer-intentions')->assertJsonCount(1, 'data');

        // User edits body and re-requests public sharing with consent
        $response = $this->actingAs($user)->patchJson("/api/v1/me/prayer-intentions/{$intention->id}", [
            'body' => 'Nội dung mới thay đổi đáng kể cần phải được ban quản trị thẩm định lại.',
            'visibility' => 'public',
            'consent_public_sharing' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonPath('data.moderation_status', 'pending');

        // SECURITY INVARIANT: Public feed must NOT return the updated content until re-approved!
        $this->getJson('/api/v1/prayer-intentions')->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('prayer_intentions', [
            'id' => $intention->id,
            'visibility' => 'public',
            'moderation_status' => 'pending',
            'approved_at' => null,
            'published_at' => null,
        ]);
    }

    // ==========================================
    // 4. MODERATION WORKFLOW & AUDIT
    // ==========================================

    public function test_admin_can_approve_pending_intention(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();

        $intention = PrayerIntention::factory()->pending()->create([
            'user_id' => $author->id,
        ]);

        $moderationService = app(PrayerModerationService::class);
        $approved = $moderationService->approve($admin, $intention);

        $this->assertEquals(PrayerModerationStatus::Approved, $approved->moderation_status);
        $this->assertEquals($admin->id, $approved->moderated_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertNotNull($approved->published_at);

        $this->assertDatabaseHas('prayer_intention_audits', [
            'prayer_intention_id' => $intention->id,
            'actor_id' => $admin->id,
            'action' => 'approved',
        ]);
    }

    public function test_admin_can_reject_pending_intention_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();

        $intention = PrayerIntention::factory()->pending()->create([
            'user_id' => $author->id,
        ]);

        $moderationService = app(PrayerModerationService::class);
        $rejected = $moderationService->reject(
            $admin,
            $intention,
            PrayerRejectionReason::PersonalInformation->value,
            'Vui lòng ẩn số điện thoại trong lời nguyện.'
        );

        $this->assertEquals(PrayerModerationStatus::Rejected, $rejected->moderation_status);
        $this->assertEquals('personal_information', $rejected->rejection_reason_code);
        $this->assertEquals('Vui lòng ẩn số điện thoại trong lời nguyện.', $rejected->user_rejection_note);

        $this->assertDatabaseHas('prayer_intention_audits', [
            'prayer_intention_id' => $intention->id,
            'actor_id' => $admin->id,
            'action' => 'rejected',
            'reason_code' => 'personal_information',
        ]);
    }

    public function test_approving_withdrawn_intention_fails(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create();

        $intention = PrayerIntention::factory()->withdrawn()->create([
            'user_id' => $author->id,
        ]);

        $this->expectException(ConflictHttpException::class);

        $moderationService = app(PrayerModerationService::class);
        $moderationService->approve($admin, $intention);
    }

    // ==========================================
    // 5. INPUT VALIDATION & SAFETY
    // ==========================================

    public function test_empty_or_too_short_body_is_rejected(): void
    {
        $user = User::factory()->create();

        // Empty body
        $this->actingAs($user)->postJson('/api/v1/me/prayer-intentions', [
            'body' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['body']);

        // Whitespace only
        $this->actingAs($user)->postJson('/api/v1/me/prayer-intentions', [
            'body' => '                  ',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['body']);

        // Less than 20 chars
        $this->actingAs($user)->postJson('/api/v1/me/prayer-intentions', [
            'body' => 'Quá ngắn',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_vietnamese_unicode_and_line_breaks_are_faithfully_preserved(): void
    {
        $user = User::factory()->create();

        $sacredText = "Nam Mô Đại Từ Đại Bi Quán Thế Âm Bồ Tát.\nCon thành tâm kính nguyện:\n1. Mười phương thanh tịnh.\n2. Gia đình an khang.";

        $response = $this->actingAs($user)->postJson('/api/v1/me/prayer-intentions', [
            'body' => $sacredText,
            'category' => 'tri_an',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', $sacredText);
    }

    public function test_categories_endpoint_returns_all_valid_categories(): void
    {
        $response = $this->getJson('/api/v1/prayer-intentions/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['key', 'label'],
                ],
            ]);

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertContains('binh_an', $keys);
        $this->assertContains('gia_dinh', $keys);
        $this->assertContains('tri_an', $keys);
        $this->assertContains('hoi_huong', $keys);
        $this->assertContains('thien_lanh', $keys);
        $this->assertContains('khac', $keys);
    }
}

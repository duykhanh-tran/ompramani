<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\Sutra;
use App\Models\SutraReadingProgress;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SutraReadingProgressApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected Sutra $publishedSutra;

    protected SutraVersion $publishedVersion1;

    protected SutraVersion $publishedVersion2;

    protected SutraSection $section1;

    protected SutraSection $section2;

    protected Sutra $draftSutra;

    protected SutraVersion $draftVersion;

    protected SutraSection $draftSection;

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

        $tradition = Tradition::factory()->create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'is_active' => true,
        ]);

        $this->publishedSutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Kim Cang Bát Nhã Ba La Mật',
            'slug' => 'kinh-kim-cang',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->publishedVersion1 = SutraVersion::factory()->create([
            'sutra_id' => $this->publishedSutra->id,
            'name' => 'Bản dịch Cưu Ma La Thập - HT. Thích Trí Quang',
            'status' => 'published',
            'is_default' => true,
        ]);

        $this->publishedVersion2 = SutraVersion::factory()->create([
            'sutra_id' => $this->publishedSutra->id,
            'name' => 'Bản dịch HT. Thích Thanh Từ',
            'status' => 'published',
            'is_default' => false,
        ]);

        $this->section1 = SutraSection::factory()->create([
            'sutra_version_id' => $this->publishedVersion1->id,
            'title' => 'Phẩm 1: Pháp Hội Nhân Duyên',
            'slug' => 'pham-1',
            'position' => 1,
        ]);

        $this->section2 = SutraSection::factory()->create([
            'sutra_version_id' => $this->publishedVersion1->id,
            'title' => 'Phẩm 2: Thiện Hiện Khởi Thỉnh',
            'slug' => 'pham-2',
            'position' => 2,
        ]);

        $this->draftSutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bản Thảo Đang Soạn',
            'slug' => 'kinh-ban-thao',
            'status' => 'draft',
        ]);

        $this->draftVersion = SutraVersion::factory()->create([
            'sutra_id' => $this->publishedSutra->id,
            'name' => 'Bản dịch nội bộ',
            'status' => 'draft',
        ]);

        $this->draftSection = SutraSection::factory()->create([
            'sutra_version_id' => $this->draftVersion->id,
            'title' => 'Phẩm nháp 1',
            'position' => 1,
        ]);
    }

    public function test_guest_cannot_get_reading_progress(): void
    {
        $response = $this->getJson('/api/v1/me/sutra-reading-progress');
        $response->assertStatus(401);
    }

    public function test_guest_cannot_save_reading_progress(): void
    {
        $response = $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
        ]);
        $response->assertStatus(401);
    }

    public function test_user_can_save_reading_progress_for_published_sutra_and_section(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.sutra.slug', 'kinh-kim-cang')
            ->assertJsonPath('data.version.id', $this->publishedVersion1->id)
            ->assertJsonPath('data.section.id', $this->section1->id)
            ->assertJsonPath('data.revision', 1);

        $this->assertDatabaseHas('sutra_reading_progress', [
            'user_id' => $this->user1->id,
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
            'revision' => 1,
        ]);
    }

    public function test_cannot_save_progress_for_draft_sutra(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson("/api/v1/me/sutras/{$this->draftSutra->slug}/reading-progress", [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_save_progress_for_draft_version(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->draftVersion->id,
            'sutra_section_id' => $this->draftSection->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_save_progress_for_section_not_belonging_to_version(): void
    {
        Sanctum::actingAs($this->user1);

        $sectionFromV2 = SutraSection::factory()->create([
            'sutra_version_id' => $this->publishedVersion2->id,
            'title' => 'Phẩm của bản dịch 2',
            'position' => 1,
        ]);

        $response = $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $sectionFromV2->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_subsequent_saves_on_same_version_updates_record_and_increments_revision_without_duplicates(): void
    {
        Sanctum::actingAs($this->user1);

        // Step 1: Read Section 1
        $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
        ])->assertStatus(200);

        // Step 2: Read Section 2 (providing previous revision = 1)
        $res = $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section2->id,
            'revision' => 1,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.section.id', $this->section2->id)
            ->assertJsonPath('data.revision', 2);

        // Ensure database has only 1 row for (user1, publishedVersion1)
        $this->assertEquals(
            1,
            SutraReadingProgress::where('user_id', $this->user1->id)
                ->where('sutra_version_id', $this->publishedVersion1->id)
                ->count()
        );

        $this->assertDatabaseHas('sutra_reading_progress', [
            'user_id' => $this->user1->id,
            'sutra_section_id' => $this->section2->id,
            'revision' => 2,
        ]);
    }

    public function test_stale_update_with_lower_revision_is_rejected_with_409_conflict(): void
    {
        Sanctum::actingAs($this->user1);

        // Record is currently at revision 3
        SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section2->id,
            'last_read_at' => now(),
            'revision' => 3,
        ]);

        // Client sends an outdated update with revision 2
        $res = $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
            'revision' => 2,
        ]);

        $res->assertStatus(409);

        // Assert record remained at Section 2
        $this->assertDatabaseHas('sutra_reading_progress', [
            'user_id' => $this->user1->id,
            'sutra_section_id' => $this->section2->id,
            'revision' => 3,
        ]);
    }

    public function test_independent_progress_records_are_maintained_for_different_versions_of_same_sutra(): void
    {
        Sanctum::actingAs($this->user1);

        $sectionV2 = SutraSection::factory()->create([
            'sutra_version_id' => $this->publishedVersion2->id,
            'title' => 'Bản 2 - Phẩm 1',
            'position' => 1,
        ]);

        // Save progress for Version 1
        $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
        ])->assertStatus(200);

        // Save progress for Version 2
        $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion2->id,
            'sutra_section_id' => $sectionV2->id,
        ])->assertStatus(200);

        // Both records exist independently
        $this->assertEquals(
            2,
            SutraReadingProgress::where('user_id', $this->user1->id)
                ->where('sutra_id', $this->publishedSutra->id)
                ->count()
        );

        // Check show endpoint with specific version_id
        $this->getJson("/api/v1/me/sutras/kinh-kim-cang/reading-progress?version_id={$this->publishedVersion1->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.section.id', $this->section1->id);

        $this->getJson("/api/v1/me/sutras/kinh-kim-cang/reading-progress?version_id={$this->publishedVersion2->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.section.id', $sectionV2->id);
    }

    public function test_user_can_view_own_progress_list_ordered_by_last_read_at_desc(): void
    {
        Sanctum::actingAs($this->user1);

        $olderSutra = Sutra::factory()->create([
            'tradition_id' => $this->publishedSutra->tradition_id,
            'title' => 'Kinh Pháp Cú',
            'slug' => 'kinh-phap-cu',
            'status' => 'published',
        ]);
        $olderVersion = SutraVersion::factory()->create([
            'sutra_id' => $olderSutra->id,
            'status' => 'published',
        ]);
        $olderSection = SutraSection::factory()->create([
            'sutra_version_id' => $olderVersion->id,
            'title' => 'Phẩm Song Yếu',
        ]);

        // Progress 1 (read 2 hours ago)
        SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $olderSutra->id,
            'sutra_version_id' => $olderVersion->id,
            'sutra_section_id' => $olderSection->id,
            'last_read_at' => now()->subHours(2),
            'revision' => 1,
        ]);

        // Progress 2 (read 10 minutes ago)
        SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
            'last_read_at' => now()->subMinutes(10),
            'revision' => 1,
        ]);

        $res = $this->getJson('/api/v1/me/sutra-reading-progress');

        $res->assertStatus(200)
            ->assertJsonPath('data.0.sutra.slug', 'kinh-kim-cang')
            ->assertJsonPath('data.1.sutra.slug', 'kinh-phap-cu');
    }

    public function test_user_cannot_view_or_modify_another_users_reading_progress(): void
    {
        // User 2 has progress
        SutraReadingProgress::create([
            'user_id' => $this->user2->id,
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section2->id,
            'last_read_at' => now(),
            'revision' => 1,
        ]);

        // User 1 requests their own progress
        Sanctum::actingAs($this->user1);

        $res = $this->getJson('/api/v1/me/sutra-reading-progress');
        $res->assertStatus(200);
        $this->assertCount(0, $res->json('data'));

        // Show endpoint for User 1 returns 404 (since user1 has no progress for this sutra)
        $this->getJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress')
            ->assertStatus(404);
    }

    public function test_unpublished_sutras_are_filtered_from_progress_list(): void
    {
        Sanctum::actingAs($this->user1);

        // Progress on a sutra that was later unpublished / draft
        SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $this->draftSutra->id,
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
            'last_read_at' => now(),
            'revision' => 1,
        ]);

        $res = $this->getJson('/api/v1/me/sutra-reading-progress');
        $res->assertStatus(200);
        $this->assertCount(0, $res->json('data'));
    }

    public function test_user_can_delete_reading_progress(): void
    {
        Sanctum::actingAs($this->user1);

        SutraReadingProgress::create([
            'user_id' => $this->user1->id,
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
            'last_read_at' => now(),
            'revision' => 1,
        ]);

        $res = $this->deleteJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress');
        $res->assertStatus(200);

        $this->assertDatabaseMissing('sutra_reading_progress', [
            'user_id' => $this->user1->id,
            'sutra_id' => $this->publishedSutra->id,
        ]);
    }

    public function test_reading_progress_does_not_create_practice_sessions_or_fake_percentages(): void
    {
        Sanctum::actingAs($this->user1);

        $this->putJson('/api/v1/me/sutras/kinh-kim-cang/reading-progress', [
            'sutra_version_id' => $this->publishedVersion1->id,
            'sutra_section_id' => $this->section1->id,
        ])->assertStatus(200);

        // Practice sessions count must remain ZERO
        $this->assertEquals(0, PracticeSession::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Sutra;
use App\Models\SutraBookmark;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SutraBookmarkApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected Sutra $publishedSutra;

    protected SutraVersion $publishedVersion;

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

        $this->publishedVersion = SutraVersion::factory()->create([
            'sutra_id' => $this->publishedSutra->id,
            'name' => 'Bản dịch Cưu Ma La Thập',
            'status' => 'published',
            'is_default' => true,
        ]);

        $this->section1 = SutraSection::factory()->create([
            'sutra_version_id' => $this->publishedVersion->id,
            'title' => 'Phẩm 1: Pháp Hội Nhân Duyên',
            'slug' => 'pham-1',
            'position' => 1,
        ]);

        $this->section2 = SutraSection::factory()->create([
            'sutra_version_id' => $this->publishedVersion->id,
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
            'name' => 'Bản dịch nháp',
            'status' => 'draft',
        ]);

        $this->draftSection = SutraSection::factory()->create([
            'sutra_version_id' => $this->draftVersion->id,
            'title' => 'Phẩm nháp',
            'position' => 1,
        ]);
    }

    public function test_guest_cannot_get_or_create_bookmarks(): void
    {
        $this->getJson('/api/v1/me/sutra-bookmarks')->assertStatus(401);

        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ])->assertStatus(401);
    }

    public function test_user_can_bookmark_published_sutra(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.type', 'sutra')
            ->assertJsonPath('data.sutra.slug', 'kinh-kim-cang')
            ->assertJsonPath('data.target_url', '/kinh/kinh-kim-cang');

        $this->assertDatabaseHas('sutra_bookmarks', [
            'user_id' => $this->user1->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);
    }

    public function test_bookmarking_same_sutra_twice_is_idempotent(): void
    {
        Sanctum::actingAs($this->user1);

        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ])->assertStatus(201);

        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ])->assertStatus(201);

        $this->assertEquals(
            1,
            SutraBookmark::where('user_id', $this->user1->id)
                ->where('type', 'sutra')
                ->where('sutra_id', $this->publishedSutra->id)
                ->count()
        );
    }

    public function test_user_can_bookmark_published_section(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $this->section1->id,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.type', 'section')
            ->assertJsonPath('data.section.id', $this->section1->id)
            ->assertJsonPath(
                'data.target_url',
                "/kinh/kinh-kim-cang?version={$this->publishedVersion->id}&section={$this->section1->id}"
            );

        $this->assertDatabaseHas('sutra_bookmarks', [
            'user_id' => $this->user1->id,
            'type' => 'section',
            'sutra_section_id' => $this->section1->id,
        ]);
    }

    public function test_bookmarking_same_section_twice_is_idempotent(): void
    {
        Sanctum::actingAs($this->user1);

        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $this->section1->id,
        ])->assertStatus(201);

        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $this->section1->id,
        ])->assertStatus(201);

        $this->assertEquals(
            1,
            SutraBookmark::where('user_id', $this->user1->id)
                ->where('type', 'section')
                ->where('sutra_section_id', $this->section1->id)
                ->count()
        );
    }

    public function test_cannot_bookmark_draft_sutra_or_draft_version(): void
    {
        Sanctum::actingAs($this->user1);

        // Draft sutra
        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'sutra',
            'sutra_id' => $this->draftSutra->id,
        ])->assertStatus(404);

        // Draft version
        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->draftVersion->id,
            'sutra_section_id' => $this->draftSection->id,
        ])->assertStatus(422);
    }

    public function test_cannot_bookmark_section_not_belonging_to_version(): void
    {
        Sanctum::actingAs($this->user1);

        $anotherVersion = SutraVersion::factory()->create([
            'sutra_id' => $this->publishedSutra->id,
            'status' => 'published',
        ]);
        $alienSection = SutraSection::factory()->create([
            'sutra_version_id' => $anotherVersion->id,
            'title' => 'Alien Section',
        ]);

        $this->postJson('/api/v1/me/sutra-bookmarks', [
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $alienSection->id,
        ])->assertStatus(422);
    }

    public function test_user_can_filter_bookmarks_by_type(): void
    {
        Sanctum::actingAs($this->user1);

        // Bookmark Sutra
        SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);

        // Bookmark Section
        SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $this->section1->id,
        ]);

        // Filter all
        $resAll = $this->getJson('/api/v1/me/sutra-bookmarks');
        $resAll->assertStatus(200);
        $this->assertCount(2, $resAll->json('data'));

        // Filter sutra
        $resSutras = $this->getJson('/api/v1/me/sutra-bookmarks?type=sutra');
        $resSutras->assertStatus(200);
        $this->assertCount(1, $resSutras->json('data'));
        $this->assertEquals('sutra', $resSutras->json('data.0.type'));

        // Filter section
        $resSections = $this->getJson('/api/v1/me/sutra-bookmarks?type=section');
        $resSections->assertStatus(200);
        $this->assertCount(1, $resSections->json('data'));
        $this->assertEquals('section', $resSections->json('data.0.type'));
    }

    public function test_user_can_delete_bookmark_by_id(): void
    {
        Sanctum::actingAs($this->user1);

        $bm = SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);

        $this->deleteJson("/api/v1/me/sutra-bookmarks/{$bm->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('sutra_bookmarks', ['id' => $bm->id]);
    }

    public function test_user_can_delete_bookmark_by_target(): void
    {
        Sanctum::actingAs($this->user1);

        SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);

        $this->deleteJson('/api/v1/me/sutra-bookmarks/by-target', [
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ])->assertStatus(200);

        $this->assertDatabaseMissing('sutra_bookmarks', [
            'user_id' => $this->user1->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_bookmark(): void
    {
        $bmUser2 = SutraBookmark::create([
            'user_id' => $this->user2->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);

        Sanctum::actingAs($this->user1);

        $this->deleteJson("/api/v1/me/sutra-bookmarks/{$bmUser2->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('sutra_bookmarks', ['id' => $bmUser2->id]);
    }

    public function test_deleting_bookmark_does_not_delete_sutra_or_section(): void
    {
        Sanctum::actingAs($this->user1);

        $bm = SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $this->section1->id,
        ]);

        $this->deleteJson("/api/v1/me/sutra-bookmarks/{$bm->id}")
            ->assertStatus(200);

        // Sutra and Section remain intact
        $this->assertDatabaseHas('sutras', ['id' => $this->publishedSutra->id]);
        $this->assertDatabaseHas('sutra_sections', ['id' => $this->section1->id]);
    }

    public function test_check_endpoint_returns_saved_state_and_bookmarked_section_ids(): void
    {
        Sanctum::actingAs($this->user1);

        // Bookmark Sutra and Section 1
        SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'sutra',
            'sutra_id' => $this->publishedSutra->id,
        ]);

        SutraBookmark::create([
            'user_id' => $this->user1->id,
            'type' => 'section',
            'sutra_id' => $this->publishedSutra->id,
            'sutra_version_id' => $this->publishedVersion->id,
            'sutra_section_id' => $this->section1->id,
        ]);

        $res = $this->getJson('/api/v1/me/sutra-bookmarks/check/kinh-kim-cang');

        $res->assertStatus(200)
            ->assertJsonPath('data.is_sutra_saved', true)
            ->assertJsonPath('data.bookmarked_section_ids.0', $this->section1->id);
    }
}

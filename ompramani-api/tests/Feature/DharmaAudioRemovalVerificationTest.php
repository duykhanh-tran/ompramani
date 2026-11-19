<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PracticeSessionStatus;
use App\Models\Article;
use App\Models\BuddhistFigure;
use App\Models\PracticeSession;
use App\Models\RecitationAudio;
use App\Models\Sutra;
use App\Models\Tradition;
use App\Models\User;
use App\Support\Search\FeatureRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DharmaAudioRemovalVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Audio Library public endpoints must no longer exist (return 404).
     */
    public function test_audio_library_public_endpoints_are_removed(): void
    {
        $this->getJson('/api/v1/audio-categories')->assertNotFound();
        $this->getJson('/api/v1/audio-tracks')->assertNotFound();
        $this->getJson('/api/v1/audio-tracks/some-track')->assertNotFound();
        $this->getJson('/api/v1/audio-playlists')->assertNotFound();
        $this->getJson('/api/v1/audio-playlists/some-playlist')->assertNotFound();
    }

    /**
     * 2. Personal audio playlist endpoints must no longer exist (return 404).
     */
    public function test_personal_audio_playlist_endpoints_are_removed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/audio-playlists')->assertNotFound();
        $this->postJson('/api/v1/me/audio-playlists', ['title' => 'Test'])->assertNotFound();
        $this->getJson('/api/v1/me/audio-playlists/1')->assertNotFound();
        $this->patchJson('/api/v1/me/audio-playlists/1', ['title' => 'Updated'])->assertNotFound();
        $this->deleteJson('/api/v1/me/audio-playlists/1')->assertNotFound();
    }

    /**
     * 3. Dharma Audio tables must not exist in database.
     */
    public function test_dharma_audio_tables_do_not_exist(): void
    {
        $this->assertFalse(Schema::hasTable('audio_tracks'), 'audio_tracks table must be dropped');
        $this->assertFalse(Schema::hasTable('audio_categories'), 'audio_categories table must be dropped');
        $this->assertFalse(Schema::hasTable('audio_playlists'), 'audio_playlists table must be dropped');
        $this->assertFalse(Schema::hasTable('audio_playlist_items'), 'audio_playlist_items table must be dropped');
    }

    /**
     * 4. Task 22 Recitation audio table MUST be preserved.
     */
    public function test_recitation_audios_table_is_preserved(): void
    {
        $this->assertTrue(Schema::hasTable('recitation_audios'), 'recitation_audios table must be preserved for Chanting Task 22');
    }

    /**
     * 5. Filament Admin must not register any Dharma Audio resources.
     */
    public function test_filament_admin_has_no_audio_resources(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->assertFalse(class_exists('App\Filament\Resources\AudioTracks\AudioTrackResource'));
        $this->assertFalse(class_exists('App\Filament\Resources\AudioCategories\AudioCategoryResource'));
        $this->assertFalse(class_exists('App\Filament\Resources\AudioPlaylists\AudioPlaylistResource'));
    }

    /**
     * 6. Feature search registry must not contain audio-library.
     */
    public function test_feature_search_does_not_contain_audio_library(): void
    {
        $results = FeatureRegistry::search('pháp âm');
        $this->assertEmpty($results);

        $resultsAudio = FeatureRegistry::search('audio');
        $this->assertEmpty($resultsAudio);
    }

    /**
     * 7. User Favorites rejects audio type with 422.
     */
    public function test_favorites_rejects_audio_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/me/favorites/audio/chu-dai-bi');
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Loại nội dung yêu thích không được hỗ trợ.');
    }

    /**
     * 8. Sutra, Article, Video favorites continue to work properly.
     */
    public function test_sutra_article_video_favorites_remain_functional(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $tradition = Tradition::factory()->create(['name' => 'Bắc Tông']);
        $sutra = Sutra::factory()->create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bát Nhã Ba La Mật Đa',
            'slug' => 'kinh-bat-nha',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->putJson("/api/v1/me/favorites/sutra/{$sutra->slug}");
        $response->assertOk()
            ->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.type', 'sutra');

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $user->id,
            'favoritable_type' => 'sutra',
            'favoritable_id' => $sutra->id,
        ]);
    }

    /**
     * 9. PracticeSessions and users are completely unharmed.
     */
    public function test_practice_sessions_and_users_are_preserved(): void
    {
        $user = User::factory()->create(['name' => 'Tu Sĩ Giác Ngộ']);

        $session = PracticeSession::create([
            'user_id' => $user->id,
            'practice_type' => 'meditation',
            'status' => PracticeSessionStatus::Completed,
            'started_at' => now()->subMinutes(30),
            'ended_at' => now(),
            'active_duration_seconds' => 1800,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Tu Sĩ Giác Ngộ']);
        $this->assertDatabaseHas('practice_sessions', ['id' => $session->id, 'active_duration_seconds' => 1800]);
    }

    /**
     * 10. Chanting & Recitation audio functionality (Task 22) remains intact.
     */
    public function test_chanting_and_recitation_audios_remain_intact(): void
    {
        $figure = BuddhistFigure::create([
            'name' => 'Đức Phật A Di Đà',
            'slug' => 'duc-phat-a-di-da',
            'figure_type' => 'buddha',
        ]);

        $recitation = RecitationAudio::create([
            'audioable_type' => BuddhistFigure::class,
            'audioable_id' => $figure->id,
            'title' => 'Nam Mô A Di Đà Phật - Âm Điệu Thanh Tịnh',
            'file_path' => 'audio/recitations/nam-mo-a-di-da-phat.mp3',
            'file_url' => 'https://example.com/recitation/nam-mo-a-di-da-phat.mp3',
            'duration_seconds' => 300,
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('recitation_audios', [
            'id' => $recitation->id,
            'title' => 'Nam Mô A Di Đà Phật - Âm Điệu Thanh Tịnh',
        ]);
    }
}

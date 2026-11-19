<?php

namespace Tests\Feature;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\MantraVersion;
use App\Models\PracticeSession;
use App\Models\RecitationAudio;
use App\Models\Tradition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecitationPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tradition $tradition;

    protected BuddhistFigure $figure;

    protected Mantra $mantra;

    protected MantraVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->tradition = Tradition::create([
            'name' => 'Bắc Truyền',
            'slug' => 'bac-truyen',
            'description' => 'Đại thừa',
            'is_active' => true,
        ]);

        $this->figure = BuddhistFigure::create([
            'tradition_id' => $this->tradition->id,
            'name' => 'Quán Thế Âm Bồ Tát',
            'slug' => 'quan-the-am-bo-tat',
            'original_name' => 'Avalokiteśvara',
            'invocation_text' => 'Nam mô Đại Bi Quán Thế Âm Bồ Tát',
            'figure_type' => 'bodhisattva',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->mantra = Mantra::create([
            'tradition_id' => $this->tradition->id,
            'buddhist_figure_id' => $this->figure->id,
            'title' => 'Chú Đại Bi',
            'slug' => 'chu-dai-bi',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->version = MantraVersion::create([
            'mantra_id' => $this->mantra->id,
            'name' => 'Bản Hán-Việt',
            'content' => 'Nam mô hắc ra đát na đa ra dạ da...',
            'language' => 'zh-Latn',
            'is_default' => true,
            'status' => 'published',
        ]);
    }

    public function test_buddhist_figure_api_returns_invocation_text_and_audios(): void
    {
        // Attach a published audio and a draft audio
        RecitationAudio::create([
            'audioable_type' => $this->figure->getMorphClass(),
            'audioable_id' => $this->figure->id,
            'title' => 'Niệm danh hiệu Quán Thế Âm',
            'file_path' => 'audio/quan-am.mp3',
            'status' => 'published',
        ]);

        RecitationAudio::create([
            'audioable_type' => $this->figure->getMorphClass(),
            'audioable_id' => $this->figure->id,
            'title' => 'Bản nháp audio',
            'file_path' => 'audio/draft.mp3',
            'status' => 'draft',
        ]);

        $response = $this->getJson("/api/v1/buddhist-figures/{$this->figure->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.invocation_text', 'Nam mô Đại Bi Quán Thế Âm Bồ Tát')
            ->assertJsonCount(1, 'data.audios')
            ->assertJsonPath('data.audios.0.title', 'Niệm danh hiệu Quán Thế Âm');
    }

    public function test_mantra_version_api_returns_published_audios_only(): void
    {
        RecitationAudio::create([
            'audioable_type' => $this->version->getMorphClass(),
            'audioable_id' => $this->version->id,
            'title' => 'Tụng Chú Đại Bi chuẩn nhịp',
            'file_path' => 'audio/chu-dai-bi.mp3',
            'duration_seconds' => 320,
            'status' => 'published',
        ]);

        RecitationAudio::create([
            'audioable_type' => $this->version->getMorphClass(),
            'audioable_id' => $this->version->id,
            'title' => 'Audio chưa duyệt',
            'file_path' => 'audio/unapproved.mp3',
            'status' => 'draft',
        ]);

        $response = $this->getJson("/api/v1/mantra-versions/{$this->version->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.audios')
            ->assertJsonPath('data.audios.0.title', 'Tụng Chú Đại Bi chuẩn nhịp')
            ->assertJsonPath('data.audios.0.duration_seconds', 320);
    }

    public function test_authenticated_user_can_start_mantra_recitation_session(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/me/practice-sessions', [
            'practice_type' => 'mantra_recitation',
            'subject_type' => 'mantra_version',
            'subject_id' => $this->version->id,
            'client_session_key' => 'test-mantra-uuid-1',
            'progress_data' => [
                'repetition_count' => 0,
                'target_repetitions' => 21,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.practice_type', 'mantra_recitation')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.subject.type', 'mantra_version')
            ->assertJsonPath('data.derived_metrics.repetition_count', 0)
            ->assertJsonPath('data.derived_metrics.target_repetitions', 21);
    }

    public function test_authenticated_user_can_start_buddha_name_recitation_session(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/me/practice-sessions', [
            'practice_type' => 'buddha_name_recitation',
            'subject_type' => 'buddhist_figure',
            'subject_id' => $this->figure->id,
            'client_session_key' => 'test-buddha-uuid-1',
            'progress_data' => [
                'repetition_count' => 0,
                'target_repetitions' => 108,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.practice_type', 'buddha_name_recitation')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.subject.title', 'Quán Thế Âm Bồ Tát')
            ->assertJsonPath('data.derived_metrics.repetition_count', 0)
            ->assertJsonPath('data.derived_metrics.target_repetitions', 108);
    }

    public function test_can_update_repetition_count_and_complete_session(): void
    {
        $session = PracticeSession::create([
            'user_id' => $this->user->id,
            'practice_type' => PracticeType::MantraRecitation,
            'status' => PracticeSessionStatus::Active,
            'subject_type' => 'mantra_version',
            'subject_id' => $this->version->id,
            'started_at' => now()->subMinutes(15),
            'active_duration_seconds' => 900,
            'last_heartbeat_at' => now(),
            'last_activity_at' => now(),
            'progress_data' => ['repetition_count' => 5],
            'sequence' => 1,
        ]);

        // Update progress
        $updateRes = $this->actingAs($this->user)->patchJson("/api/v1/me/practice-sessions/{$session->id}/progress", [
            'sequence' => 2,
            'progress_data' => [
                'repetition_count' => 21,
            ],
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.derived_metrics.repetition_count', 21);

        // Complete session
        $completeRes = $this->actingAs($this->user)->postJson("/api/v1/me/practice-sessions/{$session->id}/complete", [
            'progress_data' => [
                'repetition_count' => 21,
            ],
        ]);

        $completeRes->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.derived_metrics.repetition_count', 21);
    }
}

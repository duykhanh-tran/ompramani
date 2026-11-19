<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Sutra;
use App\Models\Tradition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyDharmaVerseRemovalVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all Daily Dharma Verse API routes have been removed and return 404.
     */
    public function test_all_dharma_verse_endpoints_return_404(): void
    {
        $this->getJson('/api/v1/dharma-verses/today')->assertNotFound();
        $this->getJson('/api/v1/dharma-verses/daily')->assertNotFound();
        $this->getJson('/api/v1/dharma-verses/daily/2026-11-10')->assertNotFound();
        $this->getJson('/api/v1/dharma-verses/test-slug')->assertNotFound();
    }

    /**
     * Test that module-specific database tables are removed.
     */
    public function test_dharma_verse_tables_do_not_exist(): void
    {
        $this->assertFalse(Schema::hasTable('daily_verse_assignments'), 'daily_verse_assignments table should not exist');
        $this->assertFalse(Schema::hasTable('dharma_verses'), 'dharma_verses table should not exist');
    }

    /**
     * Test that Sutra and Tradition models no longer define dharmaVerses relation.
     */
    public function test_models_no_longer_have_dharma_verses_relations(): void
    {
        $sutra = new Sutra();
        $this->assertFalse(method_exists($sutra, 'dharmaVerses'), 'Sutra model should not have dharmaVerses method');

        $tradition = new Tradition();
        $this->assertFalse(method_exists($tradition, 'dharmaVerses'), 'Tradition model should not have dharmaVerses method');
    }

    /**
     * Test that Calendar endpoints continue to work seamlessly without verse dependencies.
     */
    public function test_calendar_endpoints_work_intact(): void
    {
        $response = $this->getJson('/api/v1/calendar/day?date=2026-11-10');
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'solar',
                'lunar',
            ],
        ]);

        $monthResponse = $this->getJson('/api/v1/calendar/month?year=2026&month=11');
        $monthResponse->assertOk();
    }

    /**
     * Test that Sutra endpoints continue to work intact.
     */
    public function test_sutra_endpoints_work_intact(): void
    {
        $response = $this->getJson('/api/v1/sutras');
        $response->assertOk();
    }
}

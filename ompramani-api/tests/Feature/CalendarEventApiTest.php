<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Services\Calendar\FixedCalendarEventRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FixedCalendarEventRegistry::syncToDatabase();
    }

    public function test_guest_can_list_event_types(): void
    {
        $response = $this->getJson('/api/v1/calendar/event-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['key', 'label', 'description', 'color', 'priority'],
                ],
            ]);

        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertContains('buddhist_holiday', $keys);
        $this->assertContains('vegetarian_day', $keys);
        $this->assertContains('buddha_bodhisattva_observance', $keys);
        $this->assertContains('national_holiday', $keys);
        $this->assertContains('cultural_festival', $keys);
        $this->assertContains('solar_term', $keys);
    }

    public function test_events_query_returns_occurrences_for_date_range(): void
    {
        // Query around Buddha's Birthday (15/04 Lunar 2026 is 2026-05-31)
        $response = $this->getJson('/api/v1/calendar/events?from=2026-05-01&to=2026-06-05');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['occurrence_id', 'event_key', 'title', 'solar_date', 'lunar_day', 'lunar_month'],
                ],
                'meta' => ['from', 'to', 'count'],
            ]);

        $vesak = collect($response->json('data'))->firstWhere('event_key', 'dai_le_phat_dan');
        $this->assertNotNull($vesak);
        $this->assertSame('2026-05-31', $vesak['solar_date']);
        $this->assertSame(15, $vesak['lunar_day']);
        $this->assertSame(4, $vesak['lunar_month']);
    }

    public function test_events_query_validations(): void
    {
        // Missing parameters
        $this->getJson('/api/v1/calendar/events')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);

        // Invalid date format
        $this->getJson('/api/v1/calendar/events?from=invalid&to=2026-05-31')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from']);

        // To before from
        $this->getJson('/api/v1/calendar/events?from=2026-06-01&to=2026-05-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);

        // Range exceeding 366 days
        $this->getJson('/api/v1/calendar/events?from=2025-01-01&to=2026-02-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_events_day_endpoint(): void
    {
        // 2026-08-27 is 15/07 Lunar 2026 (Vu Lan)
        $response = $this->getJson('/api/v1/calendar/events/day?date=2026-08-27');

        $response->assertStatus(200)
            ->assertJsonPath('data.date', '2026-08-27')
            ->assertJsonPath('data.lunar.year', 2026)
            ->assertJsonPath('data.lunar.month', 7)
            ->assertJsonPath('data.lunar.day', 15);

        $events = collect($response->json('data.events'));
        $vuLan = $events->firstWhere('event_key', 'dai_le_vu_lan');
        $this->assertNotNull($vuLan);
        $this->assertSame('Đại Lễ Vu Lan', $vuLan['title']);
    }

    public function test_events_month_endpoint(): void
    {
        // Solar month 1 of 2026
        $response = $this->getJson('/api/v1/calendar/events/month?year=2026&month=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.month', 1)
            ->assertJsonStructure([
                'data' => [
                    'year',
                    'month',
                    'occurrences',
                    'grouped_by_date',
                ],
                'meta' => ['year', 'month', 'count'],
            ]);

        $occurrences = collect($response->json('data.occurrences'));
        // 01/01 Solar is Tết Dương Lịch
        $tetDuongLich = $occurrences->firstWhere('event_key', 'tet_duong_lich');
        $this->assertNotNull($tetDuongLich);
        $this->assertSame('2026-01-01', $tetDuongLich['solar_date']);
    }

    public function test_show_event_by_slug(): void
    {
        $response = $this->getJson('/api/v1/calendar/events/dai_le_phat_dan');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'dai_le_phat_dan')
            ->assertJsonPath('data.title', 'Đại Lễ Phật Đản')
            ->assertJsonStructure([
                'data' => [
                    'slug',
                    'title',
                    'description',
                    'event_type',
                    'rule_summary',
                    'upcoming_occurrences',
                ],
            ]);
    }
}

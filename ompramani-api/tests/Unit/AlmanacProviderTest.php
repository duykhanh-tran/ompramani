<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Almanac\Constants\EarthlyBranch;
use App\Services\Almanac\Constants\HeavenlyStem;
use App\Services\Almanac\Constants\YellowBlackDeity;
use App\Services\Almanac\Providers\ThanhLongAlmanacProvider;
use App\Services\Calendar\CalendarService;
use App\Services\Calendar\VietnameseLunisolarCalendar;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AlmanacProviderTest extends TestCase
{
    protected ThanhLongAlmanacProvider $provider;

    /**
     * @var array<string, mixed>
     */
    protected array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $engine = new VietnameseLunisolarCalendar;
        $calendarService = new CalendarService($engine);
        $this->provider = new ThanhLongAlmanacProvider($calendarService);

        $fixturePath = dirname(__DIR__).'/Fixtures/almanac-reference.json';
        $this->fixtures = json_decode((string) file_get_contents($fixturePath), true);
    }

    public function test_provider_metadata_and_integrity(): void
    {
        $meta = $this->provider->getMetadata();

        $this->assertSame('thanh_long_deity', $this->provider->getKey());
        $this->assertSame('1.0.0', $meta['version']);
        $this->assertSame('Asia/Ho_Chi_Minh', $meta['calendar_timezone']);
        $this->assertSame('1900-01-31', $meta['supported_start_date']);
        $this->assertSame('2100-12-31', $meta['supported_end_date']);
        $this->assertSame('start_date_anchored', $meta['hour_anchoring']);
        $this->assertSame('verified', $meta['verification_status']);
        $this->assertStringContainsString('Không phải giáo lý Phật giáo', $meta['disclaimer']);
    }

    public function test_independent_reference_fixtures_accuracy(): void
    {
        foreach ($this->fixtures['reference_fixtures'] as $fixture) {
            $date = $fixture['solar_date'];
            $result = $this->provider->getDayData($date);

            $this->assertSame($fixture['day_can_chi'], $result['can_chi']['day'], "Date {$date} Can Chi mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['day_classification'], $result['day_classification'], "Date {$date} classification mismatch ({$fixture['note']}).");
            $this->assertSame($fixture['deity_name'], $result['deity_name'], "Date {$date} deity mismatch ({$fixture['note']}).");

            // Verify Auspicious Hours
            $auspiciousBranchKeys = array_values(array_map(
                fn ($h) => $h['branch_key'],
                array_filter($result['hours'], fn ($h) => $h['is_auspicious'])
            ));

            $this->assertCount(6, $auspiciousBranchKeys, "Date {$date} must have exactly 6 auspicious hours.");
            $this->assertEqualsCanonicalizing($fixture['auspicious_hours'], $auspiciousBranchKeys, "Date {$date} auspicious hours list mismatch ({$fixture['note']}).");

            // Verify Zi-Hour (Giờ Tý) Can Chi
            $ziHour = $result['hours'][0];
            $this->assertSame('ty', $ziHour['branch_key']);
            $this->assertSame($fixture['zi_hour_can_chi'], $ziHour['can_chi'], "Date {$date} Giờ Tý Can Chi mismatch.");
        }
    }

    public function test_twelve_earthly_branches_intervals_and_midnight_boundary(): void
    {
        $date = '2026-02-17';
        $result = $this->provider->getDayData($date);
        $hours = $result['hours'];

        $this->assertCount(12, $hours);

        // Check each hour slot
        for ($i = 0; $i < 12; $i++) {
            $h = $hours[$i];
            $this->assertSame($i, $h['branch_index']);
            $this->assertNotEmpty($h['branch_name']);
            $this->assertNotEmpty($h['can_chi']);
            $this->assertContains($h['classification'], ['auspicious', 'inauspicious']);

            // Validate start < end timestamps
            $startTs = strtotime($h['start_local_datetime']);
            $endTs = strtotime($h['end_local_datetime']);
            $this->assertGreaterThan($startTs, $endTs, "Branch {$h['branch_key']} end must be after start.");
            $this->assertSame(7200, $endTs - $startTs, "Branch {$h['branch_key']} must span exactly 2 hours (7200s).");
        }

        // Giờ Tý midnight crossing check
        $zi = $hours[0];
        $this->assertTrue($zi['crosses_midnight']);
        $this->assertSame('2026-02-17 23:00:00', $zi['start_local_datetime']);
        $this->assertSame('2026-02-18 01:00:00', $zi['end_local_datetime']);

        // Check other 11 hours do not cross midnight
        for ($i = 1; $i < 12; $i++) {
            $this->assertFalse($hours[$i]['crosses_midnight'], "Branch {$hours[$i]['branch_key']} should not cross midnight.");
        }
    }

    public function test_registries_and_sexagenary_cycles(): void
    {
        $this->assertCount(12, EarthlyBranch::ALL);
        $this->assertCount(10, HeavenlyStem::ALL);
        $this->assertCount(12, YellowBlackDeity::ALL);

        // 6 Auspicious & 6 Inauspicious Deities
        $auspiciousDeities = array_filter(YellowBlackDeity::ALL, fn ($d) => $d['is_auspicious']);
        $this->assertCount(6, $auspiciousDeities);

        $inauspiciousDeities = array_filter(YellowBlackDeity::ALL, fn ($d) => ! $d['is_auspicious']);
        $this->assertCount(6, $inauspiciousDeities);
    }

    public function test_unsupported_dates_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->provider->getDayData('1899-12-31');
    }

    public function test_monthly_summary_has_exact_days_and_auspicious_count(): void
    {
        $monthData = $this->provider->getMonthData(2026, 2);

        $this->assertSame(2026, $monthData['solar_year']);
        $this->assertSame(2, $monthData['solar_month']);
        $this->assertCount(28, $monthData['days']); // February 2026 has 28 days

        foreach ($monthData['days'] as $day) {
            $this->assertSame(6, $day['auspicious_hours_count']);
            $this->assertContains($day['day_classification'], ['hoang_dao', 'hac_dao']);
            $this->assertSame('verified', $day['verification_status']);
        }
    }

    public function test_server_timezone_independence(): void
    {
        $originalTz = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');

            $resultUtc = $this->provider->getDayData('2026-02-17');

            date_default_timezone_set('America/New_York');
            $resultNy = $this->provider->getDayData('2026-02-17');

            $this->assertSame($resultUtc['can_chi']['day'], $resultNy['can_chi']['day']);
            $this->assertSame($resultUtc['day_classification'], $resultNy['day_classification']);
            $this->assertSame($resultUtc['hours'][0]['start_local_datetime'], $resultNy['hours'][0]['start_local_datetime']);
            $this->assertSame('2026-02-17 23:00:00', $resultUtc['hours'][0]['start_local_datetime']);
        } finally {
            date_default_timezone_set($originalTz);
        }
    }
}

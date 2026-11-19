<?php

declare(strict_types=1);

namespace App\Services\Almanac;

use App\Services\Almanac\Contracts\AlmanacProviderInterface;
use App\Services\Almanac\Providers\ThanhLongAlmanacProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AlmanacService
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * @var array<string, AlmanacProviderInterface>
     */
    protected array $providers = [];

    protected string $primaryKey;

    public function __construct(
        ThanhLongAlmanacProvider $thanhLongProvider
    ) {
        $this->registerProvider($thanhLongProvider, true);
    }

    /**
     * Register an almanac provider.
     */
    public function registerProvider(AlmanacProviderInterface $provider, bool $isPrimary = false): void
    {
        $this->providers[$provider->getKey()] = $provider;

        if ($isPrimary || ! isset($this->primaryKey)) {
            $this->primaryKey = $provider->getKey();
        }
    }

    /**
     * Get a registered provider by key, or primary provider if key is null.
     *
     * @throws ValidationException
     */
    public function getProvider(?string $key = null): AlmanacProviderInterface
    {
        if ($key === null || trim($key) === '') {
            $key = $this->primaryKey;
        }

        if (! isset($this->providers[$key])) {
            throw ValidationException::withMessages([
                'method' => [sprintf('Phương pháp lịch (method) "%s" không tồn tại hoặc chưa được hỗ trợ.', $key)],
            ]);
        }

        return $this->providers[$key];
    }

    /**
     * List all available almanac methods and their metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        foreach ($this->providers as $key => $provider) {
            $meta = $provider->getMetadata();
            $meta['is_primary'] = ($key === $this->primaryKey);
            $methods[] = $meta;
        }

        return $methods;
    }

    /**
     * Get almanac data for a single day.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getDay(?string $dateString = null, ?string $methodKey = null): array
    {
        if ($dateString === null || trim($dateString) === '') {
            $dateString = CarbonImmutable::now(self::TIMEZONE)->format('Y-m-d');
        }

        $this->validateDateString($dateString);

        $provider = $this->getProvider($methodKey);
        $providerKey = $provider->getKey();
        $version = $provider->getMetadata()['version'] ?? '1.0.0';

        $cacheKey = "almanac:day:{$providerKey}:{$version}:{$dateString}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($provider, $dateString): array {
            $dayData = $provider->getDayData($dateString);
            $dayData['has_override'] = false;

            return $dayData;
        });
    }

    /**
     * Get almanac summaries for all days in a solar month.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getMonth(int $year, int $month, ?string $methodKey = null): array
    {
        if ($year < 1900 || $year > 2100) {
            throw ValidationException::withMessages([
                'year' => ['Năm phải nằm trong khoảng từ 1900 đến 2100.'],
            ]);
        }

        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'month' => ['Tháng phải nằm trong khoảng từ 1 đến 12.'],
            ]);
        }

        $provider = $this->getProvider($methodKey);
        $providerKey = $provider->getKey();
        $version = $provider->getMetadata()['version'] ?? '1.0.0';

        $cacheKey = "almanac:month:{$providerKey}:{$version}:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($provider, $year, $month): array {
            return $provider->getMonthData($year, $month);
        });
    }

    /**
     * Clear cached responses for a given date and method.
     */
    public function clearCacheForDate(string $dateString, string $methodKey): void
    {
        $version = '1.0.0';
        Cache::forget("almanac:day:{$methodKey}:{$version}:{$dateString}");

        [$y, $m] = explode('-', $dateString);
        Cache::forget("almanac:month:{$methodKey}:{$version}:".(int) $y.':'.(int) $m);
    }

    /**
     * Merge hour overrides into 12 hours array.
     *
     * @param  array<int, array<string, mixed>>  $hours
     * @param  array<string, mixed>  $hourOverrides
     * @return array<int, array<string, mixed>>
     */
    protected function applyHourOverrides(array $hours, array $hourOverrides): array
    {
        foreach ($hours as &$hour) {
            $branchKey = $hour['branch_key'] ?? '';
            if (isset($hourOverrides[$branchKey])) {
                $ov = $hourOverrides[$branchKey];
                if (is_array($ov)) {
                    if (isset($ov['classification'])) {
                        $hour['classification'] = $ov['classification'];
                        $hour['is_auspicious'] = ($ov['classification'] === 'auspicious');
                    }
                    if (isset($ov['deity_name'])) {
                        $hour['deity_name'] = $ov['deity_name'];
                    }
                } elseif (is_string($ov)) {
                    $hour['classification'] = $ov;
                    $hour['is_auspicious'] = ($ov === 'auspicious');
                }
            }
        }
        unset($hour);

        return $hours;
    }

    /**
     * Validate solar date format YYYY-MM-DD.
     *
     * @throws ValidationException
     */
    protected function validateDateString(string $dateString): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            throw ValidationException::withMessages([
                'date' => ['Định dạng ngày dương lịch không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD.'],
            ]);
        }

        [$y, $m, $d] = array_map('intval', explode('-', $dateString));
        if (! checkdate($m, $d, $y)) {
            throw ValidationException::withMessages([
                'date' => ['Ngày dương lịch được cung cấp không phải là ngày hợp lệ trong lịch.'],
            ]);
        }
    }
}

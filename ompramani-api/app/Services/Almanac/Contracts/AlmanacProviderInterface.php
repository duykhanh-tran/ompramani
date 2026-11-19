<?php

declare(strict_types=1);

namespace App\Services\Almanac\Contracts;

interface AlmanacProviderInterface
{
    /**
     * Unique machine identifier for this provider/method.
     */
    public function getKey(): string;

    /**
     * Human-readable name.
     */
    public function getName(): string;

    /**
     * Complete metadata regarding algorithms, source, license, supported range.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if a given solar date string (YYYY-MM-DD) is supported.
     */
    public function isSupportedDate(string $dateString): bool;

    /**
     * Calculate almanac day data for a solar date.
     *
     * @return array<string, mixed>
     */
    public function getDayData(string $dateString): array;

    /**
     * Calculate almanac summary data for all days in a solar month.
     *
     * @return array<string, mixed>
     */
    public function getMonthData(int $year, int $month): array;
}

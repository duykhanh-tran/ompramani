<?php

declare(strict_types=1);

return [
    'system' => 'vietnamese_lunisolar',
    'timezone' => env('CALENDAR_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'timezone_offset' => 7.0,
    'min_supported_solar_date' => '1900-01-31',
    'max_supported_solar_date' => '2100-12-31',
    'min_supported_lunar_year' => 1900,
    'max_supported_lunar_year' => 2100,
    'algorithm' => [
        'name' => 'Ho Ngoc Duc / Jean Meeus Astronomical Algorithms (AA98)',
        'reference_meridian' => '105°E (UTC+7:00)',
        'license' => 'Public Domain / Free Software',
    ],
    'capabilities' => [
        'solar_to_lunar',
        'lunar_to_solar',
        'day_view',
        'month_view',
        'leap_month',
        'can_chi',
        'solar_terms',
    ],
];

<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarSystem: string
{
    case Lunar = 'lunar';
    case Solar = 'solar';
    case SolarTerm = 'solar_term';

    public function label(): string
    {
        return match ($this) {
            self::Lunar => 'Âm Lịch Việt Nam (UTC+07:00)',
            self::Solar => 'Dương Lịch (Gregorian)',
            self::SolarTerm => 'Tiết Khí Mặt Trời (UTC+07:00)',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarEventRecurrence: string
{
    case AnnualLunar = 'annual_lunar';
    case MonthlyLunar = 'monthly_lunar';
    case OneTimeLunar = 'one_time_lunar';
    case AnnualSolar = 'annual_solar';
    case OneTimeSolar = 'one_time_solar';
    case SolarTerm = 'solar_term';

    public function label(): string
    {
        return match ($this) {
            self::AnnualLunar => 'Hằng Năm theo Âm Lịch (Ngày/Tháng AL)',
            self::MonthlyLunar => 'Hằng Tháng theo Âm Lịch (Các ngày trong tháng AL)',
            self::OneTimeLunar => 'Một Lần duy nhất theo Âm Lịch',
            self::AnnualSolar => 'Hằng Năm theo Dương Lịch (Ngày/Tháng DL)',
            self::OneTimeSolar => 'Một Lần duy nhất theo Dương Lịch',
            self::SolarTerm => 'Tiết Khí Mặt Trời (Tính theo kinh độ Mặt Trời)',
        };
    }
}

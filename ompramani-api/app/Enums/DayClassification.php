<?php

declare(strict_types=1);

namespace App\Enums;

enum DayClassification: string
{
    case HoangDao = 'hoang_dao';
    case HacDao = 'hac_dao';
    case Neutral = 'neutral';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::HoangDao => 'Hoàng đạo',
            self::HacDao => 'Hắc đạo',
            self::Neutral => 'Bình thường',
            self::Unknown => 'Chưa xác định',
        };
    }

    public function isAuspicious(): bool
    {
        return $this === self::HoangDao;
    }
}

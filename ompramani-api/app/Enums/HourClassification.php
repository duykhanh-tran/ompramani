<?php

declare(strict_types=1);

namespace App\Enums;

enum HourClassification: string
{
    case Auspicious = 'auspicious';
    case Inauspicious = 'inauspicious';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Auspicious => 'Hoàng đạo',
            self::Inauspicious => 'Hắc đạo',
            self::Unknown => 'Chưa xác định',
        };
    }

    public function isAuspicious(): bool
    {
        return $this === self::Auspicious;
    }
}

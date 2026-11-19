<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticeOccurrenceStatus: string
{
    case Planned = 'planned';
    case Practiced = 'practiced';
    case Skipped = 'skipped';

    /**
     * Display label in Vietnamese.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Dự kiến',
            self::Practiced => 'Đã thực hành',
            self::Skipped => 'Đã bỏ qua',
        };
    }
}

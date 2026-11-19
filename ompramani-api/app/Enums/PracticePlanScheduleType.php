<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticePlanScheduleType: string
{
    case Once = 'once';
    case Weekly = 'weekly';

    /**
     * Display label in Vietnamese.
     */
    public function label(): string
    {
        return match ($this) {
            self::Once => 'Một lần',
            self::Weekly => 'Hằng tuần',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticeReminderScheduleType: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Một lần',
            self::Daily => 'Hằng ngày',
            self::Weekly => 'Hằng tuần',
        };
    }
}

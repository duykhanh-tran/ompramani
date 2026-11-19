<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerResponseReportStatus: string
{
    case Pending = 'pending';
    case Dismissed = 'dismissed';
    case Actioned = 'actioned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý',
            self::Dismissed => 'Đã bác bỏ',
            self::Actioned => 'Đã xử lý (Thu hồi lời nhắn)',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Dismissed => 'gray',
            self::Actioned => 'danger',
        };
    }
}

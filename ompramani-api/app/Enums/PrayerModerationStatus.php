<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerModerationStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Không yêu cầu',
            self::Pending => 'Chờ kiểm duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Chưa được chấp thuận',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::NotRequired => 'gray',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}

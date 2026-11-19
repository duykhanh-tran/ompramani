<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerResponseModerationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ kiểm duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Chưa được chấp thuận',
            self::Revoked => 'Đã thu hồi',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Revoked => 'gray',
        };
    }
}

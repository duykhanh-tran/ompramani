<?php

declare(strict_types=1);

namespace App\Enums;

enum TempleVerificationStatus: string
{
    case Unverified = 'unverified';
    case PendingReview = 'pending_review';
    case Verified = 'verified';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Chưa xác minh',
            self::PendingReview => 'Chờ kiểm duyệt',
            self::Verified => 'Đã xác minh',
            self::Disputed => 'Đang tranh chấp / Cần làm rõ',
        };
    }
}

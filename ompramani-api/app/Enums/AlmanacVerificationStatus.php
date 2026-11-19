<?php

declare(strict_types=1);

namespace App\Enums;

enum AlmanacVerificationStatus: string
{
    case Verified = 'verified';
    case PendingReview = 'pending_review';
    case Unavailable = 'unavailable';
    case Unsupported = 'unsupported';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Đã kiểm chứng',
            self::PendingReview => 'Chờ duyệt',
            self::Unavailable => 'Không khả dụng',
            self::Unsupported => 'Ngoài phạm vi hỗ trợ',
        };
    }
}

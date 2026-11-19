<?php

declare(strict_types=1);

namespace App\Enums;

enum AlmanacOverrideStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::InReview => 'Đang xét duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
        };
    }
}

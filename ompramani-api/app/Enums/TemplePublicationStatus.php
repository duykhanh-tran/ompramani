<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplePublicationStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::InReview => 'Đang xem xét',
            self::Published => 'Đã xuất bản',
            self::Archived => 'Đã lưu trữ',
        };
    }
}

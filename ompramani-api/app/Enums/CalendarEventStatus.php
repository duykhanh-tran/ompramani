<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarEventStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp (Draft)',
            self::InReview => 'Đang thẩm định (In Review)',
            self::Published => 'Đã xuất bản (Published)',
            self::Archived => 'Lưu trữ (Archived)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'warning',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }
}

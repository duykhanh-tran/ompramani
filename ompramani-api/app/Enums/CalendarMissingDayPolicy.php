<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarMissingDayPolicy: string
{
    case Skip = 'skip';
    case LastDay = 'last_day';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Skip => 'Bỏ Qua (Nếu tháng thiếu 29 ngày không có ngày 30)',
            self::LastDay => 'Chuyển Sang Ngày Cuối Tháng (Lấy ngày 29 nếu tháng thiếu)',
            self::ManualReview => 'Cần Xét Duyệt Thủ Công',
        };
    }
}

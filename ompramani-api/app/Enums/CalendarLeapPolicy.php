<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarLeapPolicy: string
{
    case RegularOnly = 'regular_only';
    case LeapOnly = 'leap_only';
    case Both = 'both';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::RegularOnly => 'Chỉ Tháng Thường (Mặc định - bỏ qua tháng nhuận)',
            self::LeapOnly => 'Chỉ Tháng Nhuận (Chỉ xuất hiện nếu năm có tháng nhuận đó)',
            self::Both => 'Cả Hai Tháng (Xuất hiện ở cả tháng thường và tháng nhuận)',
            self::ManualReview => 'Cần Xét Duyệt Thủ Công (Không tự động công bố khi có nhuận)',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerResponseReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case PersonalInformation = 'personal_information';
    case FraudOrSolicitation = 'fraud_or_solicitation';
    case Misinformation = 'misinformation';
    case UnsafeContent = 'unsafe_content';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam / Quảng cáo / Trùng lặp',
            self::Harassment => 'Ngôn từ công kích / Thiếu hòa nhã',
            self::PersonalInformation => 'Tiết lộ thông tin định danh cá nhân',
            self::FraudOrSolicitation => 'Kêu gọi tiền bạc / Dấu hiệu gian lận',
            self::Misinformation => 'Thông tin sai lệch / Gây hiểu lầm',
            self::UnsafeContent => 'Nội dung nguy hiểm / Khủng hoảng',
            self::Other => 'Lý do khác',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerRejectionReason: string
{
    case PersonalInformation = 'personal_information';
    case Harassment = 'harassment';
    case Spam = 'spam';
    case MisleadingClaim = 'misleading_claim';
    case UnsafeContent = 'unsafe_content';
    case Other = 'other';

    public function userExplanation(): string
    {
        return match ($this) {
            self::PersonalInformation => 'Lời nguyện chứa thông tin định danh cá nhân (họ tên đầy đủ, số điện thoại, địa chỉ hoặc liên hệ). Để bảo vệ sự riêng tư, xin vui lòng ẩn các thông tin này trước khi chia sẻ.',
            self::Harassment => 'Nội dung chưa phù hợp với tinh thần hòa nhã, tôn trọng và thanh tịnh của không gian cộng đồng.',
            self::Spam => 'Nội dung có dấu hiệu lặp lại nhiều lần hoặc chứa liên kết quảng cáo không phù hợp.',
            self::MisleadingClaim => 'Nội dung chứa thông tin hoặc tuyên bố có thể gây ngộ nhận hoặc hiểu lầm cho người đọc.',
            self::UnsafeContent => 'Nội dung đề cập đến tình huống nguy hiểm hoặc nhạy cảm cần được hỗ trợ trực tiếp từ các tổ chức chuyên môn.',
            self::Other => 'Lời nguyện chưa đáp ứng đầy đủ tiêu chuẩn chia sẻ cộng đồng tại thời điểm này.',
        };
    }

    public function adminLabel(): string
    {
        return match ($this) {
            self::PersonalInformation => 'Chứa thông tin định danh cá nhân',
            self::Harassment => 'Nội dung chưa phù hợp chuẩn mực',
            self::Spam => 'Spam / Trùng lặp / Quảng cáo',
            self::MisleadingClaim => 'Cam kết hoặc tuyên bố gây hiểu lầm',
            self::UnsafeContent => 'Nội dung nhạy cảm / Khủng hoảng',
            self::Other => 'Lý do khác',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum TempleSourceType: string
{
    case OfficialWebsite = 'official_website';
    case GovernmentRegistry = 'government_registry';
    case EditorialVerification = 'editorial_verification';
    case BookDocument = 'book_document';
    case FieldSurvey = 'field_survey';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OfficialWebsite => 'Trang thông tin chính thức',
            self::GovernmentRegistry => 'Hồ sơ di tích / Cơ quan quản lý',
            self::EditorialVerification => 'Ban biên tập xác minh thực địa',
            self::BookDocument => 'Tài liệu / Sách nghiên cứu Phật học',
            self::FieldSurvey => 'Khảo sát điền dã',
            self::Other => 'Nguồn tham khảo khác',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarEventType: string
{
    case BuddhistHoliday = 'buddhist_holiday';
    case BuddhaBodhisattvaObservance = 'buddha_bodhisattva_observance';
    case VegetarianDay = 'vegetarian_day';
    case UposathaOrObservance = 'uposatha_or_observance';
    case PracticeDay = 'practice_day';
    case OtherReligiousEvent = 'other_religious_event';
    case NationalHoliday = 'national_holiday';
    case CulturalFestival = 'cultural_festival';
    case SolarTerm = 'solar_term';

    /**
     * Vietnamese display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::BuddhistHoliday => 'Đại Lễ Phật Giáo',
            self::BuddhaBodhisattvaObservance => 'Ngày Vía Phật & Bồ Tát',
            self::VegetarianDay => 'Ngày Trai Giới (Ăn Chay)',
            self::UposathaOrObservance => 'Ngày Bố-tát & Trực Nhật',
            self::PracticeDay => 'Ngày Tu Tập & Khóa Tu',
            self::OtherReligiousEvent => 'Pháp Sự & Sự Kiện Khác',
            self::NationalHoliday => 'Ngày Lễ Quốc Gia',
            self::CulturalFestival => 'Lễ Hội Dân Gian & Truyền Thống',
            self::SolarTerm => 'Tiết Khí Mặt Trời',
        };
    }

    /**
     * Brief description.
     */
    public function description(): string
    {
        return match ($this) {
            self::BuddhistHoliday => 'Các ngày đại lễ thiêng liêng trọng đại trong truyền thống Phật giáo (Phật Đản, Vu Lan, Thành Đạo...)',
            self::BuddhaBodhisattvaObservance => 'Các ngày kỷ niệm đản sinh, xuất gia, thành đạo, nhập diệt của Chư Phật, Bồ Tát và Thánh Chúng',
            self::VegetarianDay => 'Các ngày giữ giới ăn chay thanh tịnh (Nhị trai, Tứ trai, Lục trai, Thập trai)',
            self::UposathaOrObservance => 'Các ngày trưởng tịnh tụng giới (Bố-tát) hoặc ngày sám hối định kỳ',
            self::PracticeDay => 'Các thời khóa tu tập tập trung hoặc khóa huân tu',
            self::OtherReligiousEvent => 'Các sự kiện văn hóa, lịch sử và hoạt động Phật sự khác',
            self::NationalHoliday => 'Các ngày kỷ niệm và ngày lễ lớn của quốc gia',
            self::CulturalFestival => 'Các ngày lễ tết và phong tục tập quán truyền thống dân gian',
            self::SolarTerm => 'Thời điểm chuyển giao các tiết khí quan trọng theo chu kỳ Mặt Trời',
        };
    }

    /**
     * Sort priority when multiple events happen on the same day.
     */
    public function priority(): int
    {
        return match ($this) {
            self::BuddhistHoliday => 1,
            self::BuddhaBodhisattvaObservance => 2,
            self::VegetarianDay => 3,
            self::UposathaOrObservance => 4,
            self::PracticeDay => 5,
            self::OtherReligiousEvent => 6,
            self::NationalHoliday => 7,
            self::CulturalFestival => 8,
            self::SolarTerm => 9,
        };
    }

    /**
     * Filament badge color.
     */
    public function color(): string
    {
        return match ($this) {
            self::BuddhistHoliday => 'warning',
            self::BuddhaBodhisattvaObservance => 'primary',
            self::VegetarianDay => 'success',
            self::UposathaOrObservance => 'info',
            self::PracticeDay => 'gray',
            self::OtherReligiousEvent => 'secondary',
            self::NationalHoliday => 'danger',
            self::CulturalFestival => 'info',
            self::SolarTerm => 'gray',
        };
    }
}

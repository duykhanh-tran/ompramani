<?php

declare(strict_types=1);

namespace App\Services\Almanac\Constants;

use App\Enums\DayClassification;
use App\Enums\HourClassification;

final class YellowBlackDeity
{
    /**
     * The 12 Yellow/Black Path Deities (Thập Nhị Thần Sát).
     */
    public const ALL = [
        0 => [
            'index' => 0,
            'key' => 'thanh_long',
            'name' => 'Thanh Long',
            'is_auspicious' => true,
            'day_classification' => DayClassification::HoangDao,
            'hour_classification' => HourClassification::Auspicious,
        ],
        1 => [
            'index' => 1,
            'key' => 'minh_duong',
            'name' => 'Minh Đường',
            'is_auspicious' => true,
            'day_classification' => DayClassification::HoangDao,
            'hour_classification' => HourClassification::Auspicious,
        ],
        2 => [
            'index' => 2,
            'key' => 'thien_hinh',
            'name' => 'Thiên Hình',
            'is_auspicious' => false,
            'day_classification' => DayClassification::HacDao,
            'hour_classification' => HourClassification::Inauspicious,
        ],
        3 => [
            'index' => 3,
            'key' => 'chu_tuoc',
            'name' => 'Chu Tước',
            'is_auspicious' => false,
            'day_classification' => DayClassification::HacDao,
            'hour_classification' => HourClassification::Inauspicious,
        ],
        4 => [
            'index' => 4,
            'key' => 'kim_quy',
            'name' => 'Kim Quỹ',
            'is_auspicious' => true,
            'day_classification' => DayClassification::HoangDao,
            'hour_classification' => HourClassification::Auspicious,
        ],
        5 => [
            'index' => 5,
            'key' => 'thien_duc',
            'name' => 'Thiên Đức',
            'is_auspicious' => true,
            'day_classification' => DayClassification::HoangDao,
            'hour_classification' => HourClassification::Auspicious,
        ],
        6 => [
            'index' => 6,
            'key' => 'bach_ho',
            'name' => 'Bạch Hổ',
            'is_auspicious' => false,
            'day_classification' => DayClassification::HacDao,
            'hour_classification' => HourClassification::Inauspicious,
        ],
        7 => [
            'index' => 7,
            'key' => 'ngoc_duong',
            'name' => 'Ngọc Đường',
            'is_auspicious' => true,
            'day_classification' => DayClassification::HoangDao,
            'hour_classification' => HourClassification::Auspicious,
        ],
        8 => [
            'index' => 8,
            'key' => 'thien_lao',
            'name' => 'Thiên Lao',
            'is_auspicious' => false,
            'day_classification' => DayClassification::HacDao,
            'hour_classification' => HourClassification::Inauspicious,
        ],
        9 => [
            'index' => 9,
            'key' => 'huyen_vu',
            'name' => 'Huyền Vũ',
            'is_auspicious' => false,
            'day_classification' => DayClassification::HacDao,
            'hour_classification' => HourClassification::Inauspicious,
        ],
        10 => [
            'index' => 10,
            'key' => 'tu_menh',
            'name' => 'Tư Mệnh',
            'is_auspicious' => true,
            'day_classification' => DayClassification::HoangDao,
            'hour_classification' => HourClassification::Auspicious,
        ],
        11 => [
            'index' => 11,
            'key' => 'cau_tran',
            'name' => 'Câu Trận',
            'is_auspicious' => false,
            'day_classification' => DayClassification::HacDao,
            'hour_classification' => HourClassification::Inauspicious,
        ],
    ];

    /**
     * Get deity metadata by index (0..11).
     *
     * @return array{index: int, key: string, name: string, is_auspicious: bool, day_classification: DayClassification, hour_classification: HourClassification}
     */
    public static function getByIndex(int $index): array
    {
        $normalized = ($index % 12 + 12) % 12;

        return self::ALL[$normalized];
    }
}

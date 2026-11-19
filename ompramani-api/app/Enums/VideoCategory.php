<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoCategory: string
{
    case DharmaTalk = 'dharma_talk';
    case BuddhistTeaching = 'buddhist_teaching';
    case MeditationGuidance = 'meditation_guidance';
    case SutraStudy = 'sutra_study';
    case BuddhistCulture = 'buddhist_culture';
    case BuddhistHistory = 'buddhist_history';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DharmaTalk => 'Pháp Thoại',
            self::BuddhistTeaching => 'Giáo Lý Phật Giáo',
            self::MeditationGuidance => 'Hướng Dẫn Thiền',
            self::SutraStudy => 'Học Kinh',
            self::BuddhistCulture => 'Văn Hóa Phật Giáo',
            self::BuddhistHistory => 'Lịch Sử Phật Giáo',
            self::Other => 'Chủ Đề Khác',
        };
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function toRegistry(): array
    {
        return array_map(
            fn (self $c): array => [
                'key' => $c->value,
                'label' => $c->label(),
            ],
            self::cases()
        );
    }
}

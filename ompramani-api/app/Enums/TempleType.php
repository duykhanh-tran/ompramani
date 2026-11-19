<?php

declare(strict_types=1);

namespace App\Enums;

enum TempleType: string
{
    case Temple = 'temple';
    case MeditationMonastery = 'meditation_monastery';
    case Monastery = 'monastery';
    case Vihara = 'vihara';
    case PracticeCenter = 'practice_center';
    case BuddhistCenter = 'buddhist_center';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Temple => 'Chùa',
            self::MeditationMonastery => 'Thiền viện',
            self::Monastery => 'Tu viện',
            self::Vihara => 'Tịnh xá',
            self::PracticeCenter => 'Đạo tràng',
            self::BuddhistCenter => 'Trung tâm Phật giáo',
            self::Other => 'Cơ sở Phật giáo khác',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Temple => 'Cơ sở tự viện Phật giáo truyền thống.',
            self::MeditationMonastery => 'Đạo tràng chuyên thực hành thiền định.',
            self::Monastery => 'Cơ sở tu học tập trung của tăng ni.',
            self::Vihara => 'Cơ sở tự viện thuộc hệ phái Khất sĩ hoặc Nam truyền.',
            self::PracticeCenter => 'Nơi sinh hoạt tu học, niệm Phật của Phật tử.',
            self::BuddhistCenter => 'Trung tâm văn hóa, giáo dục và nghiên cứu Phật học.',
            self::Other => 'Các cơ sở, địa điểm sinh hoạt Phật giáo khác.',
        };
    }

    /**
     * @return array<int, array{key: string, label: string, description: string}>
     */
    public static function toRegistry(): array
    {
        return array_map(
            fn (self $type) => [
                'key' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ],
            self::cases()
        );
    }
}

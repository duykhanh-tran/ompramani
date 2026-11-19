<?php

namespace App\Enums;

enum PracticeType: string
{
    case SutraReading = 'sutra_reading';
    case SutraRecitation = 'sutra_recitation';
    case MantraRecitation = 'mantra_recitation';
    case BuddhaNameRecitation = 'buddha_name_recitation';
    case Meditation = 'meditation';
    case Mala = 'mala';
    case BellWoodenFish = 'bell_wooden_fish';

    /**
     * Display label in Vietnamese.
     */
    public function label(): string
    {
        return match ($this) {
            self::SutraReading => 'Đọc Kinh',
            self::SutraRecitation => 'Tụng Kinh',
            self::MantraRecitation => 'Trì Chú',
            self::BuddhaNameRecitation => 'Niệm Phật',
            self::Meditation => 'Thiền Định',
            self::Mala => 'Tràng Hạt',
            self::BellWoodenFish => 'Chuông & Mõ',
        };
    }

    /**
     * Allowed subject types (aliases) for this practice type.
     *
     * @return array<int, string>
     */
    public function allowedSubjectTypes(): array
    {
        return match ($this) {
            self::SutraReading, self::SutraRecitation => ['sutra', 'sutra_version', 'sutra_section'],
            self::MantraRecitation => ['mantra', 'mantra_version'],
            self::BuddhaNameRecitation => ['buddhist_figure'],
            self::Mala => ['mantra', 'mantra_version', 'buddhist_figure'],
            self::Meditation, self::BellWoodenFish => [],
        };
    }

    /**
     * Whether a subject reference is required for this practice type.
     */
    public function isSubjectRequired(): bool
    {
        return match ($this) {
            self::SutraReading, self::SutraRecitation, self::MantraRecitation, self::BuddhaNameRecitation => true,
            self::Meditation, self::Mala, self::BellWoodenFish => false,
        };
    }
}

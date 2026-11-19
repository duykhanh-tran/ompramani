<?php

declare(strict_types=1);

namespace App\Services\Almanac\Constants;

final class HeavenlyStem
{
    /**
     * The 10 Heavenly Stems in order.
     */
    public const ALL = [
        0 => ['index' => 0, 'key' => 'giap', 'label' => 'Giáp'],
        1 => ['index' => 1, 'key' => 'at', 'label' => 'Ất'],
        2 => ['index' => 2, 'key' => 'binh', 'label' => 'Bính'],
        3 => ['index' => 3, 'key' => 'dinh', 'label' => 'Đinh'],
        4 => ['index' => 4, 'key' => 'mau', 'label' => 'Mậu'],
        5 => ['index' => 5, 'key' => 'ky', 'label' => 'Kỷ'],
        6 => ['index' => 6, 'key' => 'canh', 'label' => 'Canh'],
        7 => ['index' => 7, 'key' => 'tan', 'label' => 'Tân'],
        8 => ['index' => 8, 'key' => 'nham', 'label' => 'Nhâm'],
        9 => ['index' => 9, 'key' => 'quy', 'label' => 'Quý'],
    ];

    /**
     * Map label to index.
     *
     * @var array<string, int>
     */
    public const LABEL_TO_INDEX = [
        'Giáp' => 0,
        'Ất' => 1,
        'Bính' => 2,
        'Đinh' => 3,
        'Mậu' => 4,
        'Kỷ' => 5,
        'Canh' => 6,
        'Tân' => 7,
        'Nhâm' => 8,
        'Quý' => 9,
    ];

    /**
     * Map key to index.
     *
     * @var array<string, int>
     */
    public const KEY_TO_INDEX = [
        'giap' => 0,
        'at' => 1,
        'binh' => 2,
        'dinh' => 3,
        'mau' => 4,
        'ky' => 5,
        'canh' => 6,
        'tan' => 7,
        'nham' => 8,
        'quy' => 9,
    ];

    /**
     * Get stem metadata by index (0..9).
     *
     * @return array{index: int, key: string, label: string}
     */
    public static function getByIndex(int $index): array
    {
        $normalized = ($index % 10 + 10) % 10;

        return self::ALL[$normalized];
    }

    /**
     * Find stem index from Vietnamese label (e.g. "Giáp").
     */
    public static function findIndexByLabel(string $label): ?int
    {
        return self::LABEL_TO_INDEX[trim($label)] ?? null;
    }

    /**
     * Find stem index from machine key (e.g. "giap").
     */
    public static function findIndexByKey(string $key): ?int
    {
        return self::KEY_TO_INDEX[trim($key)] ?? null;
    }
}

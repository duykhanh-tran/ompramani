<?php

declare(strict_types=1);

namespace App\Services\Almanac\Constants;

final class EarthlyBranch
{
    /**
     * The 12 Earthly Branches in cyclical order.
     */
    public const ALL = [
        0 => [
            'index' => 0,
            'key' => 'ty',
            'label' => 'Tý',
            'start_time' => '23:00',
            'end_time' => '01:00',
            'crosses_midnight' => true,
        ],
        1 => [
            'index' => 1,
            'key' => 'suu',
            'label' => 'Sửu',
            'start_time' => '01:00',
            'end_time' => '03:00',
            'crosses_midnight' => false,
        ],
        2 => [
            'index' => 2,
            'key' => 'dan',
            'label' => 'Dần',
            'start_time' => '03:00',
            'end_time' => '05:00',
            'crosses_midnight' => false,
        ],
        3 => [
            'index' => 3,
            'key' => 'mao',
            'label' => 'Mão',
            'start_time' => '05:00',
            'end_time' => '07:00',
            'crosses_midnight' => false,
        ],
        4 => [
            'index' => 4,
            'key' => 'thin',
            'label' => 'Thìn',
            'start_time' => '07:00',
            'end_time' => '09:00',
            'crosses_midnight' => false,
        ],
        5 => [
            'index' => 5,
            'key' => 'ty_snake',
            'label' => 'Tỵ',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'crosses_midnight' => false,
        ],
        6 => [
            'index' => 6,
            'key' => 'ngo',
            'label' => 'Ngọ',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'crosses_midnight' => false,
        ],
        7 => [
            'index' => 7,
            'key' => 'mui',
            'label' => 'Mùi',
            'start_time' => '13:00',
            'end_time' => '15:00',
            'crosses_midnight' => false,
        ],
        8 => [
            'index' => 8,
            'key' => 'than',
            'label' => 'Thân',
            'start_time' => '15:00',
            'end_time' => '17:00',
            'crosses_midnight' => false,
        ],
        9 => [
            'index' => 9,
            'key' => 'dau',
            'label' => 'Dậu',
            'start_time' => '17:00',
            'end_time' => '19:00',
            'crosses_midnight' => false,
        ],
        10 => [
            'index' => 10,
            'key' => 'tuat',
            'label' => 'Tuất',
            'start_time' => '19:00',
            'end_time' => '21:00',
            'crosses_midnight' => false,
        ],
        11 => [
            'index' => 11,
            'key' => 'hoi',
            'label' => 'Hợi',
            'start_time' => '21:00',
            'end_time' => '23:00',
            'crosses_midnight' => false,
        ],
    ];

    /**
     * Map label to index.
     *
     * @var array<string, int>
     */
    public const LABEL_TO_INDEX = [
        'Tý' => 0,
        'Sửu' => 1,
        'Dần' => 2,
        'Mão' => 3,
        'Thìn' => 4,
        'Tỵ' => 5,
        'Ngọ' => 6,
        'Mùi' => 7,
        'Thân' => 8,
        'Dậu' => 9,
        'Tuất' => 10,
        'Hợi' => 11,
    ];

    /**
     * Map key to index.
     *
     * @var array<string, int>
     */
    public const KEY_TO_INDEX = [
        'ty' => 0,
        'suu' => 1,
        'dan' => 2,
        'mao' => 3,
        'thin' => 4,
        'ty_snake' => 5,
        'ngo' => 6,
        'mui' => 7,
        'than' => 8,
        'dau' => 9,
        'tuat' => 10,
        'hoi' => 11,
    ];

    /**
     * Get branch metadata by index (0..11).
     *
     * @return array{index: int, key: string, label: string, start_time: string, end_time: string, crosses_midnight: bool}
     */
    public static function getByIndex(int $index): array
    {
        $normalized = ($index % 12 + 12) % 12;

        return self::ALL[$normalized];
    }

    /**
     * Find branch index from Vietnamese name (e.g. "Tý", "Thìn").
     */
    public static function findIndexByLabel(string $label): ?int
    {
        return self::LABEL_TO_INDEX[trim($label)] ?? null;
    }

    /**
     * Find branch index from machine key (e.g. "ty", "thin").
     */
    public static function findIndexByKey(string $key): ?int
    {
        return self::KEY_TO_INDEX[trim($key)] ?? null;
    }
}

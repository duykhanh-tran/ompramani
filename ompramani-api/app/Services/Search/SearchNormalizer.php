<?php

namespace App\Services\Search;

use Normalizer;

class SearchNormalizer
{
    /**
     * Map of common Vietnamese and Sanskrit diacritic characters to plain ASCII.
     *
     * @var array<string, string>
     */
    protected static array $charMap = [
        // Vietnamese vowels
        'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
        'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
        'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',

        'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
        'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',

        'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',

        'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
        'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
        'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',

        'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
        'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',

        'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',

        'đ' => 'd', 'Đ' => 'D',

        // Sanskrit / Pali romanization diacritics
        'ā' => 'a', 'Ā' => 'A',
        'ī' => 'i', 'Ī' => 'I',
        'ū' => 'u', 'Ū' => 'U',
        'ṛ' => 'r', 'Ṛ' => 'R', 'ṝ' => 'r', 'Ṝ' => 'R',
        'ḷ' => 'l', 'Ḷ' => 'L', 'ḹ' => 'l', 'Ḹ' => 'L',
        'ē' => 'e', 'Ē' => 'E',
        'ō' => 'o', 'Ō' => 'O',
        'ṃ' => 'm', 'Ṃ' => 'M', 'ṁ' => 'm', 'Ṁ' => 'M',
        'ḥ' => 'h', 'Ḥ' => 'H',
        'ṅ' => 'n', 'Ṅ' => 'N',
        'ñ' => 'n', 'Ñ' => 'N',
        'ṇ' => 'n', 'Ṇ' => 'N',
        'ṭ' => 't', 'Ṭ' => 'T',
        'ḍ' => 'd', 'Ḍ' => 'D',
        'ś' => 's', 'Ś' => 'S',
        'ṣ' => 's', 'Ṣ' => 'S',
        'ć' => 'c', 'Ć' => 'C',
    ];

    /**
     * Remove accents/diacritics from text without modifying original case.
     */
    public static function removeAccents(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Direct character map replacement first for exact matches
        $mapped = strtr($text, self::$charMap);

        // Decompose any remaining Unicode diacritics if Normalizer extension is available
        if (class_exists(Normalizer::class)) {
            $decomposed = Normalizer::normalize($mapped, Normalizer::FORM_D);
            if ($decomposed !== false) {
                // Remove Unicode non-spacing marks (\p{Mn})
                $stripped = preg_replace('/\p{Mn}/u', '', $decomposed);
                if ($stripped !== null) {
                    $mapped = $stripped;
                }
            }
        }

        return $mapped;
    }

    /**
     * Normalize text for search: lowercase, remove accents, collapse multiple spaces, trim.
     */
    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $clean = self::cleanQuery($text);
        $unaccented = self::removeAccents($clean);

        return trim(preg_replace('/\s+/u', ' ', $unaccented) ?? '');
    }

    /**
     * Clean and lowercase search query while retaining accents.
     */
    public static function cleanQuery(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (class_exists(Normalizer::class)) {
            $normalizedForm = Normalizer::normalize($text, Normalizer::FORM_C);
            if ($normalizedForm !== false) {
                $text = $normalizedForm;
            }
        }

        $lowered = mb_strtolower($text, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $lowered) ?? '');
    }
}

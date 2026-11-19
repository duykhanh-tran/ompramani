<?php

namespace App\Support\Search;

use App\Services\Search\SearchNormalizer;

class FeatureRegistry
{
    /**
     * @var array<int, array{type: string, title: string, slug: string, url: string, keywords: array<int, string>}>
     */
    protected static array $features = [
        [
            'type' => 'feature',
            'title' => 'Tràng hạt',
            'slug' => 'mala',
            'url' => '/trang-hat',
            'keywords' => ['trang hat', 'tràng hạt', 'chuỗi hạt', 'chuoi hat', 'mala', 'chuỗi tràng'],
        ],
        [
            'type' => 'feature',
            'title' => 'Thiền định',
            'slug' => 'meditation',
            'url' => '/thien',
            'keywords' => ['thien', 'thiền', 'thien dinh', 'thiền định', 'meditation', 'toa thien', 'tọa thiền', 'quan niem hoi tho', 'quán niệm hơi thở', 'anapanasati'],
        ],
        [
            'type' => 'feature',
            'title' => 'Chuông mõ',
            'slug' => 'bell-bowl',
            'url' => '/chuong-mo',
            'keywords' => ['chuong mo', 'chuông mõ', 'chuong', 'chuông', 'mo', 'mõ'],
        ],
        [
            'type' => 'feature',
            'title' => 'Niệm Phật',
            'slug' => 'recitation',
            'url' => '/niem-phat',
            'keywords' => ['niem phat', 'niệm phật', 'niem', 'niệm', 'nam mo a di da phat', 'recitation', 'may niem'],
        ],
        [
            'type' => 'feature',
            'title' => 'Trì chú',
            'slug' => 'mantra-practice',
            'url' => '/tri-chu',
            'keywords' => ['tri chu', 'trì chú', 'tung chu', 'tụng chú', 'chu dai bi', 'om mani padme hum', 'mantra'],
        ],
    ];

    /**
     * Search features matching query.
     *
     * @return array<int, array{type: string, title: string, slug: string, url: string}>
     */
    public static function search(string $query): array
    {
        $normalizedQuery = SearchNormalizer::normalize($query);
        if ($normalizedQuery === '') {
            return [];
        }

        $results = [];

        foreach (self::$features as $feature) {
            $matched = false;

            // Match title
            $normalizedTitle = SearchNormalizer::normalize($feature['title']);
            if (str_contains($normalizedTitle, $normalizedQuery) || str_contains($normalizedQuery, $normalizedTitle)) {
                $matched = true;
            }

            // Match slug
            if (! $matched && (str_contains($feature['slug'], $normalizedQuery) || str_contains($normalizedQuery, $feature['slug']))) {
                $matched = true;
            }

            // Match keywords
            if (! $matched) {
                foreach ($feature['keywords'] as $keyword) {
                    $normKeyword = SearchNormalizer::normalize($keyword);
                    if (str_contains($normKeyword, $normalizedQuery) || str_contains($normalizedQuery, $normKeyword)) {
                        $matched = true;
                        break;
                    }
                }
            }

            if ($matched) {
                $results[] = [
                    'type' => 'feature',
                    'title' => $feature['title'],
                    'slug' => $feature['slug'],
                    'url' => $feature['url'],
                ];
            }
        }

        return $results;
    }

    /**
     * Get all registered features.
     *
     * @return array<int, array{type: string, title: string, slug: string, url: string, keywords: array<int, string>}>
     */
    public static function all(): array
    {
        return self::$features;
    }
}

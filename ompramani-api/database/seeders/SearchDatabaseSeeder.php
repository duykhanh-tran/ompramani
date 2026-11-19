<?php

namespace Database\Seeders;

use App\Models\SearchAlias;
use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Services\Search\SearchNormalizer;
use Illuminate\Database\Seeder;

class SearchDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Traditions
        $bacTruyen = Tradition::firstOrCreate(
            ['slug' => 'bac-truyen'],
            ['name' => 'Bắc Truyền', 'description' => 'Phật giáo Đại thừa phát triển', 'is_active' => true]
        );

        $namTruyen = Tradition::firstOrCreate(
            ['slug' => 'nam-truyen'],
            ['name' => 'Nam Truyền', 'description' => 'Phật giáo Nguyên thủy Theravada', 'is_active' => true]
        );

        // 2. Kinh A Di Đà
        $sutraAdida = Sutra::updateOrCreate(
            ['slug' => 'kinh-a-di-da'],
            [
                'tradition_id' => $bacTruyen->id,
                'title' => 'Kinh A Di Đà',
                'original_title' => 'Amitābha Sūtra',
                'short_description' => 'Bản kinh trọng yếu về cõi Cực Lạc và Đức Phật A Di Đà.',
                'status' => 'published',
                'published_at' => now()->subDays(10),
            ]
        );

        $this->ensureAliases($sutraAdida, ['Amitabha', 'A Di Da']);

        $versionAdida = SutraVersion::updateOrCreate(
            ['sutra_id' => $sutraAdida->id, 'name' => 'Bản dịch HT Thích Trí Tịnh'],
            [
                'translator' => 'Thích Trí Tịnh',
                'language' => 'vi',
                'status' => 'published',
                'is_default' => true,
            ]
        );

        SutraSection::firstOrCreate(
            ['sutra_version_id' => $versionAdida->id, 'slug' => 'chinh-van'],
            [
                'title' => 'Chính văn Kinh A Di Đà',
                'content' => 'Từ đây qua phương Tây quá mười muôn ức cõi Phật, có thế giới tên là Cực Lạc, cõi đó có Đức Phật hiệu là A Di Đà...',
                'position' => 1,
                'source_page_start' => 1,
                'source_page_end' => 5,
            ]
        );

        // 3. Kinh Địa Tạng
        $sutraDiaTang = Sutra::firstOrCreate(
            ['slug' => 'kinh-dia-tang'],
            [
                'tradition_id' => $bacTruyen->id,
                'title' => 'Kinh Địa Tạng',
                'original_title' => 'Kṣitigarbha Bodhisattva Pūrvapraṇidhāna Sūtra',
                'short_description' => 'Địa Tạng Bồ Tát Bổn Nguyện Kinh nói về công đức hiếu thảo cứu mẹ.',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ]
        );

        $this->ensureAliases($sutraDiaTang, ['Địa Tạng', 'Ksitigarbha', 'Dia Tang']);

        $versionDiaTang = SutraVersion::firstOrCreate(
            ['sutra_id' => $sutraDiaTang->id, 'name' => 'Bản dịch phổ thông'],
            [
                'translator' => 'Thích Trí Tịnh',
                'language' => 'vi',
                'status' => 'published',
                'is_default' => true,
            ]
        );

        SutraSection::firstOrCreate(
            ['sutra_version_id' => $versionDiaTang->id, 'slug' => 'cung-troi-dao-loi'],
            [
                'title' => 'Thần thông trên cung trời Đao Lợi',
                'content' => 'Lúc bấy giờ Đức Phật Thích Ca Mâu Ni ở trên cung trời Đao Lợi vì thân mẫu mà thuyết pháp...',
                'position' => 1,
                'source_page_start' => 1,
                'source_page_end' => 8,
            ]
        );

        // 4. Kinh Pháp Cú
        $sutraPhapCu = Sutra::firstOrCreate(
            ['slug' => 'kinh-phap-cu'],
            [
                'tradition_id' => $namTruyen->id,
                'title' => 'Kinh Pháp Cú',
                'original_title' => 'Dhammapada',
                'short_description' => 'Tuyển tập 423 kệ ngôn của Đức Phật trong Tiểu Bộ Kinh.',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ]
        );

        $this->ensureAliases($sutraPhapCu, ['Dhammapada', 'Phap Cu']);

        $versionPhapCu = SutraVersion::firstOrCreate(
            ['sutra_id' => $sutraPhapCu->id, 'name' => 'Bản dịch HT Thích Minh Châu'],
            [
                'translator' => 'Thích Minh Châu',
                'language' => 'vi',
                'status' => 'published',
                'is_default' => true,
            ]
        );

        SutraSection::firstOrCreate(
            ['sutra_version_id' => $versionPhapCu->id, 'slug' => 'pham-song-yeu'],
            [
                'title' => 'Phẩm Song Yếu',
                'content' => 'Ý dẫn đầu các pháp, ý làm chủ ý tạo. Nếu với ý ô nhiễm, nói năng hay hành động, khổ não bước theo sau...',
                'position' => 1,
                'source_page_start' => 1,
                'source_page_end' => 4,
            ]
        );
    }

    /**
     * @param  array<int, string>  $aliases
     */
    private function ensureAliases(Sutra $sutra, array $aliases): void
    {
        foreach ($aliases as $alias) {
            SearchAlias::firstOrCreate(
                [
                    'searchable_type' => 'sutra',
                    'searchable_id' => $sutra->id,
                    'alias' => $alias,
                ],
                [
                    'normalized_alias' => SearchNormalizer::normalize($alias),
                ]
            );
        }
    }
}

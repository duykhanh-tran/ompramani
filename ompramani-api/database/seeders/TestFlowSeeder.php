<?php

namespace Database\Seeders;

use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use Illuminate\Database\Seeder;

class TestFlowSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tradition: Bắc Truyền
        $tradition = Tradition::firstOrCreate(
            ['slug' => 'bac-truyen'],
            ['name' => 'Bắc Truyền', 'description' => 'Phật giáo Bắc Truyền', 'is_active' => true]
        );
        echo "Tradition: {$tradition->name} (ID: {$tradition->id})\n";

        // 2. Sutra: Kinh A Di Đà
        $sutra = Sutra::firstOrCreate(
            ['slug' => 'kinh-a-di-da'],
            [
                'tradition_id' => $tradition->id,
                'title' => 'Kinh A Di Đà',
                'original_title' => 'Amitābha Sūtra',
                'status' => 'published',
            ]
        );
        echo "Sutra: {$sutra->title} (ID: {$sutra->id})\n";

        // 3. SutraVersion: Bản tiếng Việt
        $version = SutraVersion::firstOrCreate(
            ['sutra_id' => $sutra->id, 'name' => 'Bản tiếng Việt'],
            [
                'translator' => 'Thích Trí Tịnh',
                'language' => 'vi',
                'publisher' => 'NXB Tôn Giáo',
                'publication_year' => '2020',
                'status' => 'published',
                'is_default' => true,
            ]
        );
        echo "Version: {$version->name} (ID: {$version->id})\n";

        // 4. SutraSections: Phần mở đầu / Chính văn / Hồi hướng
        $s1 = SutraSection::updateOrCreate(
            ['sutra_version_id' => $version->id, 'slug' => 'phan-mo-dau'],
            [
                'title' => 'Phần mở đầu',
                'position' => 1,
                'source_page_start' => 1,
                'source_page_end' => 3,
                'content' => 'Tôi nghe như vầy: Một thời Đức Phật ở nơi vườn Cấp Cô Độc, rừng cây Kỳ Đà nước Xá Vệ...',
            ]
        );
        echo "Section 1: {$s1->title} (ID: {$s1->id}, Position: {$s1->position})\n";

        $s2 = SutraSection::updateOrCreate(
            ['sutra_version_id' => $version->id, 'slug' => 'chinh-van'],
            [
                'title' => 'Chính văn',
                'position' => 2,
                'parent_id' => null,
                'source_page_start' => 4,
                'source_page_end' => 20,
                'content' => 'Bấy giờ Đức Phật bảo Trưởng lão Xá Lợi Phất: Từ đây qua phương Tây quá mười muôn ức cõi Phật...',
            ]
        );
        echo "Section 2: {$s2->title} (ID: {$s2->id}, Position: {$s2->position})\n";

        $s3 = SutraSection::updateOrCreate(
            ['sutra_version_id' => $version->id, 'slug' => 'hoi-huong'],
            [
                'title' => 'Hồi hướng',
                'position' => 3,
                'parent_id' => null,
                'source_page_start' => 21,
                'source_page_end' => 22,
                'content' => 'Nguyện đem công đức này, hướng về khắp tất cả, đệ tử và chúng sanh, đều trọn thành Phật đạo.',
            ]
        );
        echo "Section 3: {$s3->title} (ID: {$s3->id}, Position: {$s3->position})\n";

        // Test nested section (child section of Chính văn)
        $s2_1 = SutraSection::updateOrCreate(
            ['sutra_version_id' => $version->id, 'slug' => 'cong-duc-trang-nghiem'],
            [
                'title' => 'Công đức trang nghiêm Cực Lạc',
                'parent_id' => $s2->id,
                'position' => 1,
                'source_page_start' => 5,
                'source_page_end' => 10,
                'content' => 'Lại nữa, Xá Lợi Phất! Cõi nước Cực Lạc có bảy tầng lan can, bảy tầng lưới giăng, bảy hàng cây báu...',
            ]
        );
        echo "Section 2.1 (Child): {$s2_1->title} (Parent: {$s2_1->parent->title})\n";

        // Verify relationships
        echo "\n--- VERIFICATION ---\n";
        echo 'Tradition sutras count: '.$tradition->sutras()->count()."\n";
        echo 'Sutra versions count: '.$sutra->versions()->count()."\n";
        echo 'Version allSections count: '.$version->allSections()->count()."\n";
        echo 'Version root sections count: '.$version->sections()->count()."\n";
        echo 'Chính văn children count: '.$s2->children()->count()."\n";
    }
}

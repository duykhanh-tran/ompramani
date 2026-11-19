<?php

namespace Database\Seeders;

use App\Models\BuddhistFigure;
use App\Models\Mantra;
use App\Models\MantraVersion;
use App\Models\Tradition;
use Illuminate\Database\Seeder;

class RecitationContentSeeder extends Seeder
{
    public function run(): void
    {
        $bacTruyen = Tradition::firstOrCreate(
            ['slug' => 'bac-truyen'],
            ['name' => 'Bắc Truyền', 'description' => 'Phật giáo Đại thừa phát triển', 'is_active' => true]
        );

        $matTong = Tradition::firstOrCreate(
            ['slug' => 'mat-tong'],
            ['name' => 'Mật Tông', 'description' => 'Kim Cang Thừa / Mật Tông Tây Tạng', 'is_active' => true]
        );

        // 1. Quán Thế Âm Bồ Tát
        $quanAm = BuddhistFigure::updateOrCreate(
            ['slug' => 'quan-the-am-bo-tat'],
            [
                'tradition_id' => $bacTruyen->id,
                'name' => 'Quán Thế Âm Bồ Tát',
                'original_name' => 'Avalokiteśvara Bodhisattva',
                'invocation_text' => 'Nam mô Đại Bi Quán Thế Âm Bồ Tát',
                'figure_type' => 'bodhisattva',
                'short_description' => 'Hiện thân của lòng Đại Bi, lắng nghe tiếng kêu cứu khổ của muôn loài chúng sinh khắp mười phương.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        // 2. Đức Phật A Di Đà
        $aDiDa = BuddhistFigure::updateOrCreate(
            ['slug' => 'duc-phat-a-di-da'],
            [
                'tradition_id' => $bacTruyen->id,
                'name' => 'Đức Phật A Di Đà',
                'original_name' => 'Amitābha Buddha',
                'invocation_text' => 'Nam mô A Di Đà Phật',
                'figure_type' => 'buddha',
                'short_description' => 'Giáo chủ cõi Tây Phương Cực Lạc, biểu trưng cho vô lượng quang và vô lượng thọ.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        // 3. Đức Phật Thích Ca Mâu Ni
        $thichCa = BuddhistFigure::updateOrCreate(
            ['slug' => 'duc-phat-thich-ca-mau-ni'],
            [
                'tradition_id' => $bacTruyen->id,
                'name' => 'Đức Phật Thích Ca Mâu Ni',
                'original_name' => 'Śākyamuni Buddha',
                'invocation_text' => 'Nam mô Bổn Sư Thích Ca Mâu Ni Phật',
                'figure_type' => 'buddha',
                'short_description' => 'Bậc Đạo Sư giác ngộ, Đấng Từ Phụ khai sáng đạo Phật trong cõi Ta Bà.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        // 4. Địa Tạng Vương Bồ Tát
        $diaTang = BuddhistFigure::updateOrCreate(
            ['slug' => 'dia-tang-vuong-bo-tat'],
            [
                'tradition_id' => $bacTruyen->id,
                'name' => 'Địa Tạng Vương Bồ Tát',
                'original_name' => 'Kṣitigarbha Bodhisattva',
                'invocation_text' => 'Nam mô Đại Nguyện Địa Tạng Vương Bồ Tát',
                'figure_type' => 'bodhisattva',
                'short_description' => 'Vị đại Bồ Tát với đại nguyện cứu độ hết thảy chúng sinh trong chốn u đồ địa ngục.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        // 5. Thần chú: Chú Đại Bi
        $daiBi = Mantra::updateOrCreate(
            ['slug' => 'chu-dai-bi'],
            [
                'tradition_id' => $bacTruyen->id,
                'buddhist_figure_id' => $quanAm->id,
                'title' => 'Chú Đại Bi',
                'original_title' => 'Mahā Karuṇā Dhāraṇī',
                'short_description' => 'Đại Bi Tâm Đà Ra Ni gồm 84 câu thần chú linh ứng của Bồ Tát Quán Thế Âm.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        MantraVersion::updateOrCreate(
            ['mantra_id' => $daiBi->id, 'name' => 'Bản Hán-Việt truyền thống'],
            [
                'language' => 'zh-Latn',
                'script' => 'latin',
                'content' => "Nam mô Hắc ra đát na, đa ra dạ da.\nNam mô a rị da, bà lô kiết đế, thước bát ra da, bồ đề tát đỏa bà da, ma ha tát đỏa bà da, ma ha ca lô ni ca da.\nÁn, tát bàn ra phạt duệ, số đát na đát tỏa.\nNam mô tất kiết lật đỏa, y mông a rị da, bà lô kiết đế, thất Phật ra lăng đà bà.\nNam mô na ra cẩn trì, hê rị ma ha bàn đà sa mế, tát bà a tha đậu du bằng, a thệ dựng, tát bà tát đa, na ma bà tát đa, na ma bà già, ma phạt đạt đậu, đát điệt tha.\nÁn, a bà lô hê, lô ca đế, ca ra đế, di hê rị, ma ha bồ đề tát đỏa, tát bà tát bà, ma ra ma ra, ma hê ma hê, rị đà dựng, cu lô cu lô, kiết mông, độ lô độ lô, phạt xà da đế, ma ha phạt xà da đế, đà ra đà ra, địa rị ni, thất Phật ra da, giá ra giá ra, mạ mạ, phạt ma ra, mục đế lệ, y hê y hê, thất na thất na, a ra sâm, Phật ra xá lợi, phạt sa phạt sâm, Phật ra xá da, hô lô hô lô ma ra, hô lô hô lô hê rị, ta ra ta ra, tất rị tất rị, tô rô tô rô, bồ đề dạ, bồ đề dạ, bồ đà dạ, bồ đà dạ, di đế rị dạ, na ra cẩn trì, địa rị sắc ni na, ba dạ ma na, ta bà ha.\nTất đà dạ, ta bà ha.\nMa ha tất đà dạ, ta bà ha.\nTất đà dũ nghệ, thất bàn ra dạ, ta bà ha.\nNa ra cẩn trì, ta bà ha.\nMa ra na ra, ta bà ha.\nTất ra tăng, a mục khê da, ta bà ha.\nTa bà ma ha, a tất đà dạ, ta bà ha.\nGiả kiết ra, a tất đà dạ, ta bà ha.\nBa đà ma, yết tất đà dạ, ta bà ha.\nNa ra cẩn trì, bàn đà ra dạ, ta bà ha.\nMa bà lợi, thắng yết ra dạ, ta bà ha.\nNam mô Hắc ra đát na, đa ra dạ da.\nNam mô a rị da, bà lô kiết đế, thước bàn ra dạ, ta bà ha.\nÁn, tất điện đô, mạn đa ra, bạt đà dạ, ta bà ha.",
                'transliteration' => "Namo ratna-trayāya, namo'ryāvalokiteśvarāya...",
                'translation' => 'Kính lễ Tam Bảo, quy kính Bồ Tát Quán Thế Âm Đại Bi...',
                'is_default' => true,
                'status' => 'published',
            ]
        );

        MantraVersion::updateOrCreate(
            ['mantra_id' => $daiBi->id, 'name' => 'Bản Phạn văn (Sanskrit)'],
            [
                'language' => 'sa',
                'script' => 'latin',
                'content' => 'Namo ratnatrayāya. Namo āryāvalokiteśvarāya bodhisattvāya mahāsattvāya mahākāruṇikāya. Oṃ sarva-ravaye śuddhanadasya. Namas kṛtvā imaṃ āryāvalokiteśvara-stotram...',
                'transliteration' => 'Namo ratnatrayaya...',
                'translation' => 'Bản nguyên âm Sanskrit ca ngợi đức từ bi vô lượng của Đức Quán Thế Âm.',
                'is_default' => false,
                'status' => 'published',
            ]
        );

        // 6. Thần chú: Lục Tự Đại Minh (Om Mani Padme Hum)
        $omMani = Mantra::updateOrCreate(
            ['slug' => 'chu-luc-tu-dai-minh'],
            [
                'tradition_id' => $matTong->id,
                'buddhist_figure_id' => $quanAm->id,
                'title' => 'Lục Tự Đại Minh Chân Ngôn',
                'original_title' => 'Ṣaḍakṣarī Mahāvidyā',
                'short_description' => 'Câu chân ngôn sáu chữ Om Mani Padme Hum nhiệm mầu của Bồ Tát Quán Thế Âm.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        MantraVersion::updateOrCreate(
            ['mantra_id' => $omMani->id, 'name' => 'Chân ngôn chuẩn Phạn'],
            [
                'language' => 'sa',
                'script' => 'latin',
                'content' => 'Oṃ Maṇi Padme Hūṃ',
                'transliteration' => 'Om Ma-ni Bát-mê Hồng',
                'translation' => 'Viên ngọc quý sáng trong đóa sen tinh khiết.',
                'is_default' => true,
                'status' => 'published',
            ]
        );

        MantraVersion::updateOrCreate(
            ['mantra_id' => $omMani->id, 'name' => 'Phiên âm Hán-Việt'],
            [
                'language' => 'zh-Latn',
                'script' => 'latin',
                'content' => 'Án Ma Ni Bát Di Hồng',
                'transliteration' => 'Om Mani Padme Hum',
                'translation' => 'Quy y viên ngọc báu ngự giữa tòa sen thơm lành.',
                'is_default' => false,
                'status' => 'published',
            ]
        );

        // 7. Thần chú: Chú Dược Sư
        $duocSu = Mantra::updateOrCreate(
            ['slug' => 'chu-duoc-su'],
            [
                'tradition_id' => $bacTruyen->id,
                'title' => 'Dược Sư Quán Đảnh Chân Ngôn',
                'original_title' => 'Bhaiṣajyaguru Dhāraṇī',
                'short_description' => 'Thần chú tiêu trừ bệnh tật khổ não, tăng trưởng phước thọ và bình an.',
                'status' => 'published',
                'published_at' => now()->subMonths(1),
            ]
        );

        MantraVersion::updateOrCreate(
            ['mantra_id' => $duocSu->id, 'name' => 'Bản Hán-Việt phổ biến'],
            [
                'language' => 'zh-Latn',
                'script' => 'latin',
                'content' => 'Nam mô bạt già phạt đế, bệ sát xã, lũ lô thích lưu ly, bác lăng nga, hắc ra xà da, đát tha yết đa da, a ra ha đế, tam miệu tam bột đà da. Đát điệt tha: Án, bệ sát thệ, bệ sát thệ, bệ sát xã, tam một yết đế, tát ha.',
                'transliteration' => 'Namo bhagavate bhaisajyaguru...',
                'translation' => 'Kính lễ Đức Thế Tôn Dược Sư Lưu Ly Quang Như Lai Ứng Cúng Chánh Biến Tri.',
                'is_default' => true,
                'status' => 'published',
            ]
        );
    }
}

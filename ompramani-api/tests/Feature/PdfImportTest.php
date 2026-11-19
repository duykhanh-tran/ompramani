<?php

namespace Tests\Feature;

use App\Enums\DocumentImportStatus;
use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Models\DocumentImport;
use App\Models\SourceDocument;
use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\User;
use App\Services\Documents\DocumentImportService;
use App\Services\Documents\Exceptions\PdfNoTextLayerException;
use App\Services\Documents\PdfTextParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PdfImportTest extends TestCase
{
    use RefreshDatabase;

    protected string $sampleTextPdfPath;

    protected string $sampleEmptyPdfPath;

    protected string $sampleDocxPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $fixturesDir = base_path('tests/Fixtures');
        $this->sampleTextPdfPath = $fixturesDir.'/sample-text.pdf';
        $this->sampleEmptyPdfPath = $fixturesDir.'/sample-empty-or-scan.pdf';
        $this->sampleDocxPath = $fixturesDir.'/sample.docx';

        // Ensure fixtures exist
        if (! file_exists($this->sampleTextPdfPath) || ! file_exists($this->sampleEmptyPdfPath) || ! file_exists($this->sampleDocxPath)) {
            $this->generateFixtures();
        }
    }

    /**
     * Helper to regenerate fixtures if missing.
     */
    private function generateFixtures(): void
    {
        $fixturesDir = base_path('tests/Fixtures');
        if (! is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0755, true);
        }

        $this->createPdfFile($this->sampleTextPdfPath, [
            [
                'KINH A DI ĐÀ',
                '',
                'LỜI MỞ ĐẦU',
                'Kinh A Di Đà là bản kinh tối trọng của Tịnh Độ Tông.',
                'Nam Mô A Di Đà Phật. Nam Mô Quán Thế Âm Bồ Tát.',
            ],
            [
                'PHẨM THỨ NHẤT',
                'Tôi nghe như vầy: Một thời Đức Phật ở nước Xá-vệ, tại rừng Kỳ-đà, vườn Cấp Cô Độc.',
                'Địa Tạng Bồ Tát Bổn Nguyện Kinh chiếu kiến ngũ uẩn giai không.',
                'Bát Nhã Ba La Mật Đa Tâm Kinh độ nhất thiết khổ ách.',
            ],
            [
                'HỒI HƯỚNG',
                'Nguyện đem công đức này, hướng về khắp tất cả.',
                'Đệ tử và chúng sanh, đều trọn thành Phật đạo.',
            ],
        ]);

        $this->createPdfFile($this->sampleEmptyPdfPath, [[], []]);
    }

    /**
     * Helper to create a valid minimal multi-page PDF with UTF-16BE text streams.
     *
     * @param  array<int, array<int, string>>  $pagesText
     */
    private function createPdfFile(string $filename, array $pagesText): void
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $numPages = count($pagesText);
        $pageObjIds = [];
        for ($i = 0; $i < $numPages; $i++) {
            $pageObjIds[] = (3 + $i * 2).' 0 R';
        }

        $offsets[2] = strlen($pdf);
        $pdf .= '2 0 obj\n<< /Type /Pages /Kids ['.implode(' ', $pageObjIds)."] /Count $numPages >>\nendobj\n";

        $fontObjId = 100;

        for ($i = 0; $i < $numPages; $i++) {
            $pageId = 3 + $i * 2;
            $contentId = 4 + $i * 2;

            $offsets[$pageId] = strlen($pdf);
            $pdf .= "$pageId 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents $contentId 0 R /Resources << /Font << /F1 $fontObjId 0 R >> >> >>\nendobj\n";

            $textLines = $pagesText[$i];
            if (empty($textLines)) {
                $stream = '';
            } else {
                $stream = "BT /F1 12 Tf 20 TL 72 700 Td \n";
                foreach ($textLines as $line) {
                    if ($line === '') {
                        $stream .= "T* \n";
                    } else {
                        $utf16 = mb_convert_encoding($line, 'UTF-16BE', 'UTF-8');
                        $hex = 'FEFF'.bin2hex($utf16);
                        $stream .= "<$hex> Tj T* \n";
                    }
                }
                $stream .= "ET\n";
            }

            $len = strlen($stream);
            $offsets[$contentId] = strlen($pdf);
            $pdf .= "$contentId 0 obj\n<< /Length $len >>\nstream\n".$stream."endstream\nendobj\n";
        }

        $offsets[$fontObjId] = strlen($pdf);
        $pdf .= "$fontObjId 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $xrefOffset = strlen($pdf);
        $maxObj = max(array_keys($offsets));
        $pdf .= "xref\n0 ".($maxObj + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            if (isset($offsets[$i])) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
            } else {
                $pdf .= "0000000000 65535 f \n";
            }
        }
        $pdf .= "trailer\n<< /Size ".($maxObj + 1)." /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF\n";

        $dir = dirname($filename);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filename, $pdf);
    }

    /**
     * 1. Admin upload PDF text hợp lệ.
     */
    public function test_1_admin_upload_valid_pdf_text(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile(
            $this->sampleTextPdfPath,
            'kinh-a-di-da.pdf',
            'application/pdf',
            null,
            true
        );

        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);

        $this->assertInstanceOf(SourceDocument::class, $sourceDoc);
        $this->assertSame('kinh-a-di-da.pdf', $sourceDoc->original_filename);
        $this->assertSame('application/pdf', $sourceDoc->mime_type);
        $this->assertNotEmpty($sourceDoc->checksum);
        $this->assertTrue(Storage::disk('local')->exists($sourceDoc->storage_path));

        $import = $service->createImport($sourceDoc, $sutra, $admin, [
            'version_name' => 'Bản dịch PDF A Di Đà',
        ]);

        $this->assertInstanceOf(DocumentImport::class, $import);
        $this->assertSame(DocumentImportStatus::Uploaded, $import->status);
    }

    /**
     * 2. Parser lấy được text.
     */
    public function test_2_parser_extracts_text(): void
    {
        $parser = new PdfTextParser;
        $items = $parser->parse($this->sampleTextPdfPath);

        $this->assertNotEmpty($items);
        $titlesAndContents = array_merge(
            array_filter(array_column($items, 'title')),
            array_filter(array_column($items, 'content'))
        );
        $combinedText = implode(' ', $titlesAndContents);

        $this->assertStringContainsString('KINH A DI ĐÀ', $combinedText);
        $this->assertStringContainsString('LỜI MỞ ĐẦU', $combinedText);
        $this->assertStringContainsString('PHẨM THỨ NHẤT', $combinedText);
        $this->assertStringContainsString('HỒI HƯỚNG', $combinedText);
    }

    /**
     * 3. Parser giữ page number.
     */
    public function test_3_parser_preserves_page_numbers(): void
    {
        $parser = new PdfTextParser;
        $items = $parser->parse($this->sampleTextPdfPath);

        // Page 1 items
        $page1Items = array_filter($items, fn ($i) => $i['source_page_start'] === 1 && $i['source_page_end'] === 1);
        $this->assertNotEmpty($page1Items);

        // Page 2 items
        $page2Items = array_filter($items, fn ($i) => $i['source_page_start'] === 2 && $i['source_page_end'] === 2);
        $this->assertNotEmpty($page2Items);

        // Page 3 items
        $page3Items = array_filter($items, fn ($i) => $i['source_page_start'] === 3 && $i['source_page_end'] === 3);
        $this->assertNotEmpty($page3Items);

        // Verify PHẨM THỨ NHẤT is on page 2
        $pham1 = collect($items)->firstWhere('title', 'PHẨM THỨ NHẤT');
        $this->assertNotNull($pham1);
        $this->assertSame(2, $pham1['source_page_start']);
        $this->assertSame(2, $pham1['source_page_end']);
    }

    /**
     * 4. Tiếng Việt không lỗi.
     */
    public function test_4_vietnamese_unicode_without_corruption(): void
    {
        $parser = new PdfTextParser;
        $items = $parser->parse($this->sampleTextPdfPath);

        $allText = implode("\n", array_map(fn ($i) => ($i['title'] ?? '')."\n".($i['content'] ?? ''), $items));

        $this->assertStringContainsString('Địa Tạng Bồ Tát', $allText);
        $this->assertStringContainsString('Quán Thế Âm Bồ Tát', $allText);
        $this->assertStringContainsString('Bát Nhã Ba La Mật Đa', $allText);
        $this->assertStringContainsString('Nam Mô A Di Đà Phật', $allText);
    }

    /**
     * 5. PDF scan/no-text bị từ chối.
     */
    public function test_5_scanned_or_no_text_pdf_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile(
            $this->sampleEmptyPdfPath,
            'scan.pdf',
            'application/pdf',
            null,
            true
        );

        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        try {
            $service->process($import);
            $this->fail('Expected PdfNoTextLayerException was not thrown.');
        } catch (PdfNoTextLayerException $e) {
            $this->assertSame(PdfNoTextLayerException::ERROR_CODE, $e::ERROR_CODE);
            $this->assertStringContainsString('không chứa lớp văn bản hoặc có thể là PDF scan', $e->getMessage());
        }

        $this->assertSame(DocumentImportStatus::Failed, $import->fresh()->status);
        $this->assertStringContainsString('PDF scan', $import->fresh()->error_message);
    }

    /**
     * 6. File không phải PDF/DOCX bị từ chối.
     */
    public function test_6_unsupported_file_format_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $txtFile = UploadedFile::fake()->create('kinh.txt', 100, 'text/plain');

        $service = app(DocumentImportService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hệ thống chỉ chấp nhận tập tin định dạng .docx hoặc .pdf.');

        $service->storeSourceDocument($txtFile, $admin);
    }

    /**
     * 7. PDF import tạo document_import.
     */
    public function test_7_pdf_import_creates_document_import(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile(
            $this->sampleTextPdfPath,
            'kinh-a-di-da.pdf',
            'application/pdf',
            null,
            true
        );

        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);

        $import = $service->createImport($sourceDoc, $sutra, $admin, [
            'version_name' => 'Bản dịch thử nghiệm',
            'language' => 'vi',
            'translator' => 'Thích Trí Tịnh',
        ]);

        $this->assertInstanceOf(DocumentImport::class, $import);
        $this->assertSame($sutra->id, $import->sutra_id);
        $this->assertSame($sourceDoc->id, $import->source_document_id);
        $this->assertSame('Bản dịch thử nghiệm', $import->version_name);
        $this->assertSame(DocumentImportStatus::Uploaded, $import->status);
    }

    /**
     * 8. Review sections được tạo.
     */
    public function test_8_review_sections_are_created(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $processed = $service->process($import);

        $this->assertSame(DocumentImportStatus::Review, $processed->status);
        $this->assertGreaterThan(0, $processed->total_sections);

        $sections = $processed->sections()->orderBy('position')->get();
        $this->assertCount($processed->total_sections, $sections);

        // Verify sections contain both heading and paragraph types
        $this->assertTrue($sections->contains(fn ($s) => $s->type->value === 'heading'));
        $this->assertTrue($sections->contains(fn ($s) => $s->type->value === 'paragraph'));
    }

    /**
     * 9. Import tạo SutraVersion.
     */
    public function test_9_import_creates_sutra_version(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin, [
            'version_name' => 'Bản PDF Đầy Đủ',
        ]);

        $service->process($import);
        $importedSutra = $service->import($import);

        $this->assertInstanceOf(Sutra::class, $importedSutra);
        $this->assertSame($sutra->id, $importedSutra->id);
        $this->assertSame($sutra->id, $import->fresh()->sutra_id);
        $this->assertNull($import->fresh()->sutra_version_id);
        $this->assertSame(DocumentImportStatus::Imported, $import->fresh()->status);
    }

    /**
     * 10. Import tạo SutraSections.
     */
    public function test_10_import_creates_sutra_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $version = $service->import($import);

        $sutraSections = $version->sections()->orderBy('position')->get();
        $this->assertNotEmpty($sutraSections);

        $titles = $sutraSections->pluck('title')->all();
        $this->assertContains('PHẨM THỨ NHẤT', $titles);
        $this->assertContains('HỒI HƯỚNG', $titles);

        $pham1 = $sutraSections->firstWhere('title', 'PHẨM THỨ NHẤT');
        $this->assertNotNull($pham1->content);
        $this->assertStringContainsString('Cấp Cô Độc', $pham1->content);
    }

    /**
     * 11. source_page_start/end được giữ.
     */
    public function test_11_source_page_start_and_end_are_preserved(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $version = $service->import($import);

        $sutraSections = $version->sections()->orderBy('position')->get();

        $pham1 = $sutraSections->firstWhere('title', 'PHẨM THỨ NHẤT');
        $this->assertNotNull($pham1);
        $this->assertSame(2, $pham1->source_page_start);
        $this->assertSame(2, $pham1->source_page_end);

        $hoiHuong = $sutraSections->firstWhere('title', 'HỒI HƯỚNG');
        $this->assertNotNull($hoiHuong);
        $this->assertSame(3, $hoiHuong->source_page_start);
        $this->assertSame(3, $hoiHuong->source_page_end);
    }

    /**
     * 12. Version là draft.
     */
    public function test_12_version_status_after_pdf_import_is_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create(['status' => 'draft']);

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $importedSutra = $service->import($import);

        $this->assertSame('draft', $importedSutra->status);
    }

    /**
     * 13. Public API không trả version draft.
     */
    public function test_13_public_api_does_not_return_draft_version(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create(['status' => 'published']);

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin, ['version_name' => 'Bản Nháp PDF']);

        $service->process($import);
        $version = $service->import($import);

        // GET /api/v1/sutras/{slug}
        $resDetails = $this->getJson("/api/v1/sutras/{$sutra->slug}");
        $resDetails->assertStatus(200);
        $versions = $resDetails->json('data.versions');
        $this->assertEmpty($versions);

        // GET /api/v1/sutras/{slug}/versions
        $resVersions = $this->getJson("/api/v1/sutras/{$sutra->slug}/versions");
        $resVersions->assertStatus(200);
        $this->assertEmpty($resVersions->json('data'));

        // GET /api/v1/sutra-versions/{id}/sections should return 404 for draft
        $resSections = $this->getJson("/api/v1/sutra-versions/{$version->id}/sections");
        $resSections->assertStatus(404);
    }

    /**
     * 14. Import lại bị chặn.
     */
    public function test_14_duplicate_import_is_prevented(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $service->import($import);

        $this->assertSame(DocumentImportStatus::Imported, $import->fresh()->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tài liệu này đã được import vào hệ thống, không thể import lần thứ hai.');

        $service->import($import);
    }

    /**
     * 15. Failure rollback transaction.
     */
    public function test_15_failure_rolls_back_entire_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);

        // Deselect all sections to trigger failure condition
        $import->sections()->update(['is_included' => false]);

        $initialVersionsCount = SutraVersion::count();
        $initialSectionsCount = SutraSection::count();

        try {
            $service->import($import);
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Không có phần nào được chọn để import.', $e->getMessage());
        }

        $this->assertSame($initialVersionsCount, SutraVersion::count());
        $this->assertSame($initialSectionsCount, SutraSection::count());
        $this->assertNotSame(DocumentImportStatus::Imported, $import->fresh()->status);
    }

    /**
     * 16. User thường không được upload PDF.
     */
    public function test_16_regular_user_cannot_access_import_center(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $adminUser = User::factory()->admin()->create();

        $this->actingAs($regularUser);
        $this->assertFalse(DocumentImportResource::canViewAny());
        $this->assertFalse(DocumentImportResource::canCreate());

        $this->actingAs($adminUser);
        $this->assertTrue(DocumentImportResource::canViewAny());
        $this->assertTrue(DocumentImportResource::canCreate());

        $this->actingAs($regularUser);
        $response = $this->get('/admin/document-imports');
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * 17. File nguồn vẫn tồn tại sau import.
     */
    public function test_17_source_pdf_file_persists_after_import(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $file = new UploadedFile($this->sampleTextPdfPath, 'kinh.pdf', 'application/pdf', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $service->import($import);

        $this->assertTrue(Storage::disk('local')->exists($sourceDoc->storage_path));
    }

    /**
     * 18. DOCX Import cũ vẫn chạy sau khi thêm PDF support.
     */
    public function test_18_docx_import_still_works_without_regression(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create(['title' => 'Kinh DOCX Regression', 'status' => 'draft']);

        $file = new UploadedFile(
            $this->sampleDocxPath,
            'kinh-sample.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin, [
            'version_name' => 'Bản DOCX Regression Test',
        ]);

        $service->process($import);

        $this->assertSame(DocumentImportStatus::Review, $import->fresh()->status);
        $this->assertGreaterThan(0, $import->fresh()->total_sections);

        $importedSutra = $service->import($import);

        $this->assertInstanceOf(Sutra::class, $importedSutra);
        $this->assertSame('draft', $importedSutra->status);
        $this->assertSame(DocumentImportStatus::Imported, $import->fresh()->status);

        $sections = $importedSutra->allSections()->orderBy('position')->get();
        $this->assertNotEmpty($sections);
        $this->assertTrue($sections->contains(fn ($s) => str_contains($s->title, 'LỜI MỞ ĐẦU') || str_contains($s->title, 'CHÍNH VĂN') || str_contains($s->title, 'HỒI HƯỚNG')));
    }
}

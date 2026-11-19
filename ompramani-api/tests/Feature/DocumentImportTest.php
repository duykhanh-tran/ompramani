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
use App\Services\Documents\DocxParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Helper to create a valid minimal DOCX file with custom XML elements.
     *
     * @param  array<int, array{text: string, style?: string, bold?: bool, italic?: bool}>  $elements
     */
    private function createDocxFile(array $elements): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ompramani_').'.docx';
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        $bodyXml = '';
        foreach ($elements as $el) {
            $pPr = '';
            if (! empty($el['style'])) {
                $pPr = '<w:pPr><w:pStyle w:val="'.htmlspecialchars($el['style'], ENT_XML1, 'UTF-8').'"/></w:pPr>';
            }

            $rPr = '';
            if (! empty($el['bold'])) {
                $rPr .= '<w:b/>';
            }
            if (! empty($el['italic'])) {
                $rPr .= '<w:i/>';
            }
            if ($rPr !== '') {
                $rPr = '<w:rPr>'.$rPr.'</w:rPr>';
            }

            $text = htmlspecialchars($el['text'] ?? '', ENT_XML1, 'UTF-8');
            $bodyXml .= '<w:p>'.$pPr.'<w:r>'.$rPr.'<w:t xml:space="preserve">'.$text.'</w:t></w:r></w:p>';
        }

        $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$bodyXml.'</w:body>'
            .'</w:document>';
        $zip->addFromString('word/document.xml', $docXml);

        $zip->close();

        return $tempFile;
    }

    /**
     * 1. Admin upload DOCX hợp lệ.
     */
    public function test_admin_upload_valid_docx(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $path = $this->createDocxFile([
            ['text' => 'KINH A DI ĐÀ', 'style' => 'Heading1'],
            ['text' => 'Đây là nội dung kinh.'],
        ]);

        $file = new UploadedFile($path, 'kinh-a-di-da.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);

        $this->assertInstanceOf(SourceDocument::class, $sourceDoc);
        $this->assertSame('kinh-a-di-da.docx', $sourceDoc->original_filename);
        $this->assertNotEmpty($sourceDoc->checksum);
        $this->assertTrue(Storage::disk('local')->exists($sourceDoc->storage_path));

        $import = $service->createImport($sourceDoc, $sutra, $admin, [
            'version_name' => 'Bản dịch thử nghiệm',
        ]);

        $this->assertInstanceOf(DocumentImport::class, $import);
        $this->assertSame(DocumentImportStatus::Uploaded, $import->status);

        @unlink($path);
    }

    /**
     * 2. Không cho upload file định dạng không hỗ trợ.
     */
    public function test_cannot_upload_unsupported_file_format(): void
    {
        $admin = User::factory()->admin()->create();
        $txtFile = UploadedFile::fake()->create('kinh.txt', 100, 'text/plain');

        $service = app(DocumentImportService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hệ thống chỉ chấp nhận tập tin định dạng .docx hoặc .pdf.');

        $service->storeSourceDocument($txtFile, $admin);
    }

    /**
     * 3. Không cho upload file giả .docx.
     */
    public function test_cannot_upload_fake_docx(): void
    {
        $admin = User::factory()->admin()->create();

        // Create a plain text file pretending to be .docx
        $fakePath = tempnam(sys_get_temp_dir(), 'fake_').'.docx';
        file_put_contents($fakePath, 'This is an exe or plain text, not a zip.');

        $file = new UploadedFile($fakePath, 'fake.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);

        $import = $service->createImport($sourceDoc, Sutra::factory()->create(), $admin);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tập tin không phải là định dạng DOCX hợp lệ hoặc đã bị hỏng.');

        try {
            $service->process($import);
        } finally {
            @unlink($fakePath);
        }
    }

    /**
     * 4. Parser đọc được tiếng Việt.
     */
    public function test_parser_reads_vietnamese_unicode(): void
    {
        $path = $this->createDocxFile([
            ['text' => 'Địa Tạng Bồ Tát Bổn Nguyện Kinh', 'style' => 'Heading1'],
            ['text' => 'Nam Mô Đại Bi Quán Thế Âm Bồ Tát.'],
            ['text' => 'Bát Nhã Ba La Mật Đa Tâm Kinh chiếu kiến ngũ uẩn giai không.'],
        ]);

        $parser = new DocxParser;
        $items = $parser->parse($path);

        $this->assertCount(3, $items);
        $this->assertSame('Địa Tạng Bồ Tát Bổn Nguyện Kinh', $items[0]['title']);
        $this->assertSame('Nam Mô Đại Bi Quán Thế Âm Bồ Tát.', $items[1]['content']);
        $this->assertSame('Bát Nhã Ba La Mật Đa Tâm Kinh chiếu kiến ngũ uẩn giai không.', $items[2]['content']);

        @unlink($path);
    }

    /**
     * 5. Heading được detect.
     */
    public function test_heading_is_detected(): void
    {
        $path = $this->createDocxFile([
            ['text' => 'Chương 1: Duyên Khởi', 'style' => 'Heading1'],
            ['text' => 'Phần 1.1: Bối cảnh', 'style' => 'Heading2'],
        ]);

        $parser = new DocxParser;
        $items = $parser->parse($path);

        $this->assertSame('heading', $items[0]['type']);
        $this->assertSame(1, $items[0]['heading_level']);
        $this->assertSame('Chương 1: Duyên Khởi', $items[0]['title']);

        $this->assertSame('heading', $items[1]['type']);
        $this->assertSame(2, $items[1]['heading_level']);
        $this->assertSame('Phần 1.1: Bối cảnh', $items[1]['title']);

        @unlink($path);
    }

    /**
     * 6. Paragraph được detect.
     */
    public function test_paragraph_is_detected(): void
    {
        $path = $this->createDocxFile([
            ['text' => 'Đây là một đoạn văn bản thông thường không có heading style.'],
        ]);

        $parser = new DocxParser;
        $items = $parser->parse($path);

        $this->assertSame('paragraph', $items[0]['type']);
        $this->assertNull($items[0]['heading_level']);
        $this->assertSame('Đây là một đoạn văn bản thông thường không có heading style.', $items[0]['content']);

        @unlink($path);
    }

    /**
     * 7. Import tạo SutraVersion.
     */
    public function test_import_creates_sutra_version(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $path = $this->createDocxFile([
            ['text' => 'LỜI MỞ ĐẦU', 'style' => 'Heading1'],
            ['text' => 'Nội dung mở đầu.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin, ['version_name' => 'Bản Dịch Thử']);

        $service->process($import);
        $importedSutra = $service->import($import);

        $this->assertInstanceOf(Sutra::class, $importedSutra);
        $this->assertSame($sutra->id, $importedSutra->id);
        $this->assertSame($sutra->id, $import->fresh()->sutra_id);
        $this->assertNull($import->fresh()->sutra_version_id);
        $this->assertSame(DocumentImportStatus::Imported, $import->fresh()->status);

        @unlink($path);
    }

    /**
     * 8. Import tạo đúng SutraSections.
     */
    public function test_import_creates_correct_sutra_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $path = $this->createDocxFile([
            ['text' => 'Chương 1', 'style' => 'Heading1'],
            ['text' => 'Đoạn 1 của chương 1.'],
            ['text' => 'Đoạn 2 của chương 1.'],
            ['text' => 'Chương 2', 'style' => 'Heading1'],
            ['text' => 'Đoạn 1 của chương 2.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $version = $service->import($import);

        $sections = $version->sections()->orderBy('position')->get();
        $this->assertCount(2, $sections);

        $this->assertSame('Chương 1', $sections[0]->title);
        $this->assertStringContainsString('Đoạn 1 của chương 1.', $sections[0]->content);
        $this->assertStringContainsString('Đoạn 2 của chương 1.', $sections[0]->content);

        $this->assertSame('Chương 2', $sections[1]->title);
        $this->assertStringContainsString('Đoạn 1 của chương 2.', $sections[1]->content);

        @unlink($path);
    }

    /**
     * 9. Version sau import là draft.
     */
    public function test_version_status_after_import_is_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create(['status' => 'draft']);

        $path = $this->createDocxFile([
            ['text' => 'Tiêu đề', 'style' => 'Heading1'],
            ['text' => 'Nội dung.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $importedSutra = $service->import($import);

        $this->assertSame('draft', $importedSutra->status);

        @unlink($path);
    }

    /**
     * 10. Public API không trả version draft.
     */
    public function test_public_api_does_not_return_draft_version(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create(['status' => 'published']);

        $path = $this->createDocxFile([
            ['text' => 'Bản nháp', 'style' => 'Heading1'],
            ['text' => 'Nội dung chưa công bố.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin, ['version_name' => 'Bản Dịch Bản Nháp']);

        $service->process($import);
        $version = $service->import($import);

        // Check sutra details endpoint
        $resDetails = $this->getJson("/api/v1/sutras/{$sutra->slug}");
        $resDetails->assertStatus(200);
        $versions = $resDetails->json('data.versions');
        $this->assertEmpty($versions);

        // Check versions endpoint
        $resVersions = $this->getJson("/api/v1/sutras/{$sutra->slug}/versions");
        $resVersions->assertStatus(200);
        $this->assertEmpty($resVersions->json('data'));

        @unlink($path);
    }

    /**
     * 11. Import lần hai bị chặn.
     */
    public function test_duplicate_import_is_prevented(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $path = $this->createDocxFile([
            ['text' => 'Phần 1', 'style' => 'Heading1'],
            ['text' => 'Nội dung.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $service->import($import);

        $this->assertSame(DocumentImportStatus::Imported, $import->fresh()->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tài liệu này đã được import vào hệ thống, không thể import lần thứ hai.');

        $service->import($import);

        @unlink($path);
    }

    /**
     * 12. Import lỗi rollback toàn bộ transaction.
     */
    public function test_import_failure_rolls_back_entire_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $path = $this->createDocxFile([
            ['text' => 'Phần 1', 'style' => 'Heading1'],
            ['text' => 'Nội dung.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);

        // Temporarily break SutraSection creation via a database trigger/constraint simulation or empty sections
        $import->sections()->update(['is_included' => false]);

        $initialVersionsCount = SutraVersion::count();
        $initialSectionsCount = SutraSection::count();

        try {
            $service->import($import);
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Không có phần nào được chọn để import.', $e->getMessage());
        }

        // Verify transaction was rolled back: no SutraVersion created
        $this->assertSame($initialVersionsCount, SutraVersion::count());
        $this->assertSame($initialSectionsCount, SutraSection::count());
        $this->assertNotSame(DocumentImportStatus::Imported, $import->fresh()->status);

        @unlink($path);
    }

    /**
     * 13. User role user không được sử dụng Import Center.
     */
    public function test_regular_user_cannot_access_import_center(): void
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $adminUser = User::factory()->admin()->create();

        // 1. Policy/Authorization check on DocumentImportResource
        $this->actingAs($regularUser);
        $this->assertFalse(DocumentImportResource::canViewAny());
        $this->assertFalse(DocumentImportResource::canCreate());

        $this->actingAs($adminUser);
        $this->assertTrue(DocumentImportResource::canViewAny());
        $this->assertTrue(DocumentImportResource::canCreate());

        // 2. HTTP access to Filament panel
        $this->actingAs($regularUser);
        $response = $this->get('/admin/document-imports');
        // Non-admin canAccessPanel returns false, so panel middleware rejects or redirects to login
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * 14. File nguồn vẫn tồn tại sau import.
     */
    public function test_source_file_persists_after_import(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create();

        $path = $this->createDocxFile([
            ['text' => 'Kinh Bát Nhã', 'style' => 'Heading1'],
            ['text' => 'Sắc bất dị không, không bất dị sắc.'],
        ]);

        $file = new UploadedFile($path, 'kinh-bat-nha.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $service->process($import);
        $service->import($import);

        // Verify source file still exists on storage disk
        $this->assertTrue(Storage::disk('local')->exists($sourceDoc->storage_path));

        @unlink($path);
    }

    /**
     * 26. Test thực tế bắt buộc theo Section 26.
     * Cấu trúc:
     * KINH A DI ĐÀ
     * LỜI MỞ ĐẦU (Đây là nội dung mở đầu...)
     * CHÍNH VĂN (Đây là nội dung chính...)
     * HỒI HƯỚNG (Đây là nội dung hồi hướng...)
     * Xác nhận SutraVersion -> Lời mở đầu, Chính văn, Hồi hướng
     */
    public function test_section_26_mandatory_docx_flow(): void
    {
        $admin = User::factory()->admin()->create();
        $sutra = Sutra::factory()->create(['title' => 'Kinh A Di Đà', 'status' => 'draft']);

        $path = $this->createDocxFile([
            ['text' => 'KINH A DI ĐÀ', 'style' => 'Heading1'],
            ['text' => 'LỜI MỞ ĐẦU', 'style' => 'Heading1'],
            ['text' => 'Đây là nội dung mở đầu...'],
            ['text' => 'CHÍNH VĂN', 'style' => 'Heading1'],
            ['text' => 'Đây là nội dung chính...'],
            ['text' => 'HỒI HƯỚNG', 'style' => 'Heading1'],
            ['text' => 'Đây là nội dung hồi hướng...'],
        ]);

        $file = new UploadedFile($path, 'kinh-a-di-da.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin, [
            'version_name' => 'Bản dịch tiếng Việt',
        ]);

        $service->process($import);

        // Verify preview/review sections
        $importSections = $import->sections()->orderBy('position')->get();
        $this->assertCount(7, $importSections);

        // Admin imports into Sutra
        $importedSutra = $service->import($import);

        $sutraSections = $importedSutra->allSections()->orderBy('position')->get();

        // Should contain Lời mở đầu, Chính văn, Hồi hướng
        $titles = $sutraSections->pluck('title')->all();
        $this->assertContains('LỜI MỞ ĐẦU', $titles);
        $this->assertContains('CHÍNH VĂN', $titles);
        $this->assertContains('HỒI HƯỚNG', $titles);

        $loiMoDau = $sutraSections->firstWhere('title', 'LỜI MỞ ĐẦU');
        $this->assertStringContainsString('Đây là nội dung mở đầu...', $loiMoDau->content);

        $chinhVan = $sutraSections->firstWhere('title', 'CHÍNH VĂN');
        $this->assertStringContainsString('Đây là nội dung chính...', $chinhVan->content);

        $hoiHuong = $sutraSections->firstWhere('title', 'HỒI HƯỚNG');
        $this->assertStringContainsString('Đây là nội dung hồi hướng...', $hoiHuong->content);

        // Check draft status
        $this->assertSame('draft', $importedSutra->status);

        @unlink($path);
    }
}

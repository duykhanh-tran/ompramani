<?php

namespace Tests\Feature;

use App\Enums\DocumentImportStatus;
use App\Filament\Resources\DocumentImports\Schemas\DocumentImportForm;
use App\Filament\Resources\Sutras\Schemas\SutraForm;
use App\Models\DocumentImport;
use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\SutraVersion;
use App\Models\Tradition;
use App\Models\User;
use App\Services\Documents\DocumentImportService;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class SutraAdminAndDocumentImportSimplificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createDocx(array $elements): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sutra_test_').'.docx';
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');

        $bodyXml = '';
        foreach ($elements as $el) {
            $pPr = ! empty($el['style']) ? '<w:pPr><w:pStyle w:val="'.htmlspecialchars($el['style'], ENT_XML1, 'UTF-8').'"/></w:pPr>' : '';
            $text = htmlspecialchars($el['text'] ?? '', ENT_XML1, 'UTF-8');
            $bodyXml .= "<w:p>{$pPr}<w:r><w:t xml:space=\"preserve\">{$text}</w:t></w:r></w:p>";
        }

        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$bodyXml.'</w:body></w:document>');
        $zip->close();

        return $tempFile;
    }

    /**
     * A1. SutraForm has exactly 11 fields.
     */
    public function test_sutra_form_has_exactly_11_fields(): void
    {
        $schema = new Schema;
        $configured = SutraForm::configure($schema);
        $components = $configured->getComponents();

        $this->assertCount(11, $components, 'SutraForm must have exactly 11 fields');

        $names = array_map(fn ($c) => $c->getName(), $components);

        $expectedNames = [
            'tradition_id',
            'title',
            'slug',
            'translator',
            'language',
            'publisher',
            'publication_year',
            'status',
            'published_at',
            'thumbnail',
            'short_description',
        ];

        $this->assertEquals($expectedNames, $names);

        // Ensure removed fields are not present
        $this->assertNotContains('original_title', $names);
        $this->assertNotContains('source', $names);
        $this->assertNotContains('notes', $names);
        $this->assertNotContains('version_name', $names);
        $this->assertNotContains('version_id', $names);
    }

    /**
     * B1. DocumentImportForm has exactly 2 fields.
     */
    public function test_document_import_form_has_exactly_2_fields(): void
    {
        $schema = new Schema;
        $configured = DocumentImportForm::configure($schema);
        $components = $configured->getComponents();

        $this->assertCount(2, $components, 'DocumentImportForm must have exactly 2 fields');

        $names = array_map(fn ($c) => $c->getName(), $components);

        $this->assertContains('sutra_id', $names);
        $this->assertContains('docx_file', $names);

        // Ensure removed fields are completely absent
        $this->assertNotContains('version_name', $names);
        $this->assertNotContains('translator', $names);
        $this->assertNotContains('language', $names);
        $this->assertNotContains('publisher', $names);
        $this->assertNotContains('source', $names);
        $this->assertNotContains('notes', $names);
    }

    /**
     * A4 & A5. Sutra creates and updates successfully with 11 fields.
     */
    public function test_sutra_can_be_created_and_updated_with_simplified_fields(): void
    {
        $tradition = Tradition::factory()->create(['name' => 'Bắc Truyền']);

        $sutra = Sutra::create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Kim Cang',
            'slug' => 'kinh-kim-cang',
            'translator' => 'Thích Thanh Từ',
            'language' => 'vi',
            'publisher' => 'NXB Tôn Giáo',
            'publication_year' => '2023',
            'status' => 'published',
            'published_at' => now(),
            'thumbnail' => 'https://example.com/cover.jpg',
            'short_description' => 'Bộ kinh Đại Thừa đốn ngộ.',
        ]);

        $this->assertDatabaseHas('sutras', [
            'id' => $sutra->id,
            'title' => 'Kinh Kim Cang',
            'translator' => 'Thích Thanh Từ',
            'status' => 'published',
        ]);

        $sutra->update([
            'translator' => 'Thiền sư Thích Nhất Hạnh',
            'publisher' => 'NXB Phương Đông',
        ]);

        $this->assertSame('Thiền sư Thích Nhất Hạnh', $sutra->fresh()->translator);
        $this->assertSame('NXB Phương Đông', $sutra->fresh()->publisher);
    }

    /**
     * C1 & C2. Import links directly to Sutra and does NOT create a SutraVersion.
     */
    public function test_import_links_directly_to_sutra_and_does_not_create_sutra_version(): void
    {
        $admin = User::factory()->admin()->create();
        $tradition = Tradition::factory()->create();
        $sutra = Sutra::create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Bát Nhã Ba La Mật Đa',
            'slug' => 'kinh-bat-nha',
            'translator' => 'HT. Thích Trí Tịnh',
            'status' => 'draft',
        ]);

        $path = $this->createDocx([
            ['text' => 'BÁT NHÃ TÂM KINH', 'style' => 'Heading1'],
            ['text' => 'Quán Tự Tại Bồ Tát hành thâm Bát nhã Ba la mật đa thời...'],
        ]);

        $file = new UploadedFile($path, 'bat-nha.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);

        // createImport without version metadata
        $import = $service->createImport($sourceDoc, $sutra, $admin);

        $this->assertSame($sutra->id, $import->sutra_id);
        $this->assertSame('HT. Thích Trí Tịnh', $import->translator);
        $this->assertNull($import->sutra_version_id);

        $service->process($import);

        $initialVersionsCount = SutraVersion::count();

        // Perform import
        $importedSutra = $service->import($import);

        // 1. Returned model is Sutra
        $this->assertInstanceOf(Sutra::class, $importedSutra);
        $this->assertSame($sutra->id, $importedSutra->id);

        // 2. NO new SutraVersion was created!
        $this->assertSame($initialVersionsCount, SutraVersion::count(), 'Zero new SutraVersions should be created');

        // 3. DocumentImport is linked to Sutra
        $freshImport = $import->fresh();
        $this->assertSame($sutra->id, $freshImport->sutra_id);
        $this->assertNull($freshImport->sutra_version_id);
        $this->assertSame(DocumentImportStatus::Imported, $freshImport->status);

        // 4. SutraSections are created directly under Sutra
        $sections = $sutra->allSections()->orderBy('position')->get();
        $this->assertNotEmpty($sections);
        $this->assertSame($sutra->id, $sections->first()->sutra_id);
        $this->assertNull($sections->first()->sutra_version_id);
        $this->assertStringContainsString('Quán Tự Tại', $sections->first()->content);

        @unlink($path);
    }

    /**
     * C7. Existing Sutra content is NOT silently overwritten.
     */
    public function test_import_rejects_overwriting_sutra_with_existing_content(): void
    {
        $admin = User::factory()->admin()->create();
        $tradition = Tradition::factory()->create();
        $sutra = Sutra::create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Đã Có Nội Dung',
            'slug' => 'kinh-da-co-noi-dung',
            'status' => 'published',
        ]);

        // Pre-existing section
        SutraSection::create([
            'sutra_id' => $sutra->id,
            'title' => 'Chương 1 Đã Có',
            'slug' => 'chuong-1-da-co',
            'content' => 'Nội dung nguyên bản bảo vệ.',
            'position' => 1,
        ]);

        $path = $this->createDocx([
            ['text' => 'Chương Mới', 'style' => 'Heading1'],
            ['text' => 'Cố tình import ghi đè.'],
        ]);

        $file = new UploadedFile($path, 'test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);
        $service->process($import);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Hệ thống từ chối import ghi đè để bảo vệ toàn vẹn dữ liệu');

        // This must throw and reject overwrite
        $service->import($import);

        @unlink($path);
    }

    /**
     * C5. Reader API displays imported sections without version parameter.
     */
    public function test_imported_sections_are_accessible_via_public_reader_api(): void
    {
        $admin = User::factory()->admin()->create();
        $tradition = Tradition::factory()->create();
        $sutra = Sutra::create([
            'tradition_id' => $tradition->id,
            'title' => 'Kinh Pháp Cú Trực Tiếp',
            'slug' => 'kinh-phap-cu-truc-tiep',
            'status' => 'published',
        ]);

        $path = $this->createDocx([
            ['text' => 'Phẩm Song Yếu', 'style' => 'Heading1'],
            ['text' => 'Ý dẫn đầu các pháp, ý làm chủ ý tạo...'],
            ['text' => 'Phẩm Không Phóng Dật', 'style' => 'Heading1'],
            ['text' => 'Không phóng dật là đường bất tử...'],
        ]);

        $file = new UploadedFile($path, 'phap-cu.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $service = app(DocumentImportService::class);
        $sourceDoc = $service->storeSourceDocument($file, $admin);
        $import = $service->createImport($sourceDoc, $sutra, $admin);
        $service->process($import);
        $service->import($import);

        // Fetch direct sections endpoint: GET /api/v1/sutras/{slug}/sections
        $response = $this->getJson("/api/v1/sutras/{$sutra->slug}/sections");
        $response->assertStatus(200);

        $sections = $response->json('data');
        $this->assertCount(2, $sections);
        $this->assertSame('Phẩm Song Yếu', $sections[0]['title']);
        $this->assertSame('Phẩm Không Phóng Dật', $sections[1]['title']);

        // Fetch section detail
        $sectionId = $sections[0]['id'];
        $detailRes = $this->getJson("/api/v1/sutra-sections/{$sectionId}");
        $detailRes->assertStatus(200);
        $this->assertStringContainsString('Ý dẫn đầu các pháp', $detailRes->json('data.content'));

        @unlink($path);
    }
}

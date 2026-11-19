<?php

namespace App\Services\Documents;

use App\Enums\DocumentImportStatus;
use App\Models\DocumentImport;
use App\Models\ImportSection;
use App\Models\SourceDocument;
use App\Models\Sutra;
use App\Models\SutraSection;
use App\Models\User;
use App\Services\Documents\Exceptions\PdfNoTextLayerException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DocumentImportService
{
    public function __construct(
        protected DocxParser $docxParser,
        protected ?PdfTextParser $pdfParser = null
    ) {
        $this->pdfParser = $pdfParser ?? app(PdfTextParser::class);
    }

    /**
     * Store uploaded DOCX or PDF file and create SourceDocument record.
     */
    public function storeSourceDocument(
        UploadedFile $file,
        User $user,
        ?string $sourceName = null,
        ?string $translator = null,
        ?string $publisher = null,
        ?string $notes = null
    ): SourceDocument {
        $disk = (string) config('ompramani.import.disk', 'local');
        $directory = (string) config('ompramani.import.directory', 'source_documents');
        $maxSizeKb = (int) config('ompramani.import.max_file_size', 20480);

        if ($file->getSize() > $maxSizeKb * 1024) {
            throw new InvalidArgumentException("Dung lượng tập tin vượt quá giới hạn {$maxSizeKb} KB.");
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['docx', 'pdf'], true)) {
            throw new InvalidArgumentException('Hệ thống chỉ chấp nhận tập tin định dạng .docx hoặc .pdf.');
        }

        $storedFilename = Str::uuid()->toString().'.'.$extension;
        $storedPath = $file->storeAs($directory, $storedFilename, $disk);

        if (! $storedPath) {
            throw new RuntimeException('Không thể lưu tập tin vào hệ thống lưu trữ.');
        }

        $checksum = hash_file('sha256', $file->getRealPath()) ?: '';

        $mimeType = $file->getClientMimeType();
        if ($extension === 'pdf') {
            $mimeType = ($mimeType && str_contains($mimeType, 'pdf')) ? $mimeType : 'application/pdf';
        } else {
            $mimeType = ($mimeType && str_contains($mimeType, 'word')) ? $mimeType : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        $sourceDocument = SourceDocument::create([
            'original_filename' => $file->getClientOriginalName(),
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize() ?: 0,
            'checksum' => $checksum,
            'source_name' => $sourceName,
            'translator' => $translator,
            'publisher' => $publisher,
            'notes' => $notes,
            'uploaded_by' => $user->id,
        ]);

        if ($extension === 'pdf') {
            Log::info('pdf uploaded', [
                'source_document_id' => $sourceDocument->id,
                'original_filename' => $sourceDocument->original_filename,
                'user_id' => $user->id,
            ]);
        } else {
            Log::info('document uploaded', [
                'source_document_id' => $sourceDocument->id,
                'original_filename' => $sourceDocument->original_filename,
                'user_id' => $user->id,
            ]);
        }

        return $sourceDocument;
    }

    /**
     * Create a DocumentImport record.
     */
    public function createImport(
        SourceDocument $sourceDocument,
        Sutra $sutra,
        User $user,
        array $versionData = []
    ): DocumentImport {
        $documentImport = DocumentImport::create([
            'source_document_id' => $sourceDocument->id,
            'sutra_id' => $sutra->id,
            'sutra_version_id' => null,
            'version_name' => $versionData['version_name'] ?? $sutra->title,
            'translator' => $sutra->translator ?? $sourceDocument->translator,
            'language' => $sutra->language ?? 'vi',
            'publisher' => $sutra->publisher ?? $sourceDocument->publisher,
            'source' => $sutra->source ?? $sourceDocument->source_name,
            'notes' => $sutra->notes ?? $sourceDocument->notes,
            'status' => DocumentImportStatus::Uploaded,
            'created_by' => $user->id,
        ]);

        return $documentImport;
    }

    /**
     * Process DOCX/PDF parsing and extract intermediate import sections.
     */
    public function process(DocumentImport $documentImport): DocumentImport
    {
        $sourceDoc = $documentImport->sourceDocument;
        $ext = strtolower(pathinfo($sourceDoc->original_filename, PATHINFO_EXTENSION));
        $pathExt = strtolower(pathinfo($sourceDoc->storage_path, PATHINFO_EXTENSION));
        $isPdf = ($ext === 'pdf' || $pathExt === 'pdf' || $sourceDoc->mime_type === 'application/pdf');

        if ($isPdf) {
            Log::info('pdf parsing started', [
                'document_import_id' => $documentImport->id,
                'source_document_id' => $documentImport->source_document_id,
            ]);
        } else {
            Log::info('processing started', [
                'document_import_id' => $documentImport->id,
                'source_document_id' => $documentImport->source_document_id,
            ]);
        }

        $documentImport->update([
            'status' => DocumentImportStatus::Processing,
            'started_at' => now(),
            'error_message' => null,
        ]);

        $disk = $sourceDoc->storage_disk;
        $storage = Storage::disk($disk);

        if (! $storage->exists($sourceDoc->storage_path)) {
            $error = 'Tập tin nguồn không tồn tại trong hệ thống lưu trữ.';
            $documentImport->update([
                'status' => DocumentImportStatus::Failed,
                'error_message' => $error,
            ]);

            Log::error($isPdf ? 'pdf parsing failed' : 'processing failed', [
                'document_import_id' => $documentImport->id,
                'error' => $error,
            ]);

            throw new RuntimeException($error);
        }

        $fullPath = $storage->path($sourceDoc->storage_path);

        try {
            $parsedItems = $isPdf
                ? $this->pdfParser->parse($fullPath)
                : $this->docxParser->parse($fullPath);

            DB::transaction(function () use ($documentImport, $parsedItems): void {
                // Clear any existing sections for retry
                $documentImport->sections()->delete();

                $position = 1;
                $headingStack = []; // [level => import_section_id]

                foreach ($parsedItems as $index => $item) {
                    $parentId = null;

                    if ($item['type'] === 'heading') {
                        $level = $item['heading_level'] ?? 1;

                        // Pop headings with level >= current level
                        foreach (array_keys($headingStack) as $stkLevel) {
                            if ($stkLevel >= $level) {
                                unset($headingStack[$stkLevel]);
                            }
                        }

                        if (! empty($headingStack)) {
                            $parentId = end($headingStack);
                        }

                        $section = ImportSection::create([
                            'document_import_id' => $documentImport->id,
                            'parent_id' => $parentId,
                            'type' => 'heading',
                            'title' => $item['title'],
                            'content' => null,
                            'position' => $position++,
                            'source_index' => $item['source_index'] ?? ($index + 1),
                            'heading_level' => $level,
                            'source_page_start' => $item['source_page_start'] ?? null,
                            'source_page_end' => $item['source_page_end'] ?? null,
                            'is_included' => true,
                        ]);

                        $headingStack[$level] = $section->id;
                    } else {
                        // Paragraph belongs to current active heading
                        if (! empty($headingStack)) {
                            $parentId = end($headingStack);
                        }

                        ImportSection::create([
                            'document_import_id' => $documentImport->id,
                            'parent_id' => $parentId,
                            'type' => 'paragraph',
                            'title' => null,
                            'content' => $item['content'],
                            'position' => $position++,
                            'source_index' => $item['source_index'] ?? ($index + 1),
                            'heading_level' => null,
                            'source_page_start' => $item['source_page_start'] ?? null,
                            'source_page_end' => $item['source_page_end'] ?? null,
                            'is_included' => true,
                        ]);
                    }
                }

                $total = count($parsedItems);

                $documentImport->update([
                    'status' => DocumentImportStatus::Review,
                    'total_sections' => $total,
                    'processed_sections' => 0,
                    'error_message' => null,
                ]);
            });

            if ($isPdf) {
                Log::info('pdf parsing completed', [
                    'document_import_id' => $documentImport->id,
                    'total_sections' => $documentImport->total_sections,
                ]);
            } else {
                Log::info('processing succeeded', [
                    'document_import_id' => $documentImport->id,
                    'total_sections' => $documentImport->total_sections,
                ]);
            }

            return $documentImport->fresh();
        } catch (Throwable $e) {
            $documentImport->update([
                'status' => DocumentImportStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            if ($isPdf) {
                Log::error('pdf parsing failed', [
                    'document_import_id' => $documentImport->id,
                    'error' => $e->getMessage(),
                    'error_code' => ($e instanceof PdfNoTextLayerException)
                        ? PdfNoTextLayerException::ERROR_CODE
                        : null,
                ]);
            } else {
                Log::error('processing failed', [
                    'document_import_id' => $documentImport->id,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Import reviewed sections into SutraSection records directly linked to Sutra.
     *
     * @throws RuntimeException
     */
    public function import(DocumentImport $documentImport): Sutra
    {
        Log::info('import started', [
            'document_import_id' => $documentImport->id,
            'sutra_id' => $documentImport->sutra_id,
        ]);

        // Guard against duplicate import
        if ($documentImport->status === DocumentImportStatus::Imported) {
            $error = 'Tài liệu này đã được import vào hệ thống, không thể import lần thứ hai.';
            Log::warning('import duplicate prevented', ['document_import_id' => $documentImport->id]);
            throw new RuntimeException($error);
        }

        $sutra = $documentImport->sutra;
        if (! $sutra) {
            $error = 'Không tìm thấy Kinh đích để import.';
            Log::error('import failed', ['document_import_id' => $documentImport->id, 'error' => $error]);
            throw new RuntimeException($error);
        }

        // Guard against overwriting existing content (Section XII)
        if ($sutra->allSections()->exists()) {
            $count = $sutra->allSections()->count();
            $error = "Kinh [{$sutra->title}] hiện đã có nội dung ({$count} phần/chương). Hệ thống từ chối import ghi đè để bảo vệ toàn vẹn dữ liệu.";
            Log::warning('import overwrite prevented', [
                'document_import_id' => $documentImport->id,
                'sutra_id' => $sutra->id,
                'existing_sections_count' => $count,
            ]);
            throw new RuntimeException($error);
        }

        return DB::transaction(function () use ($documentImport, $sutra): Sutra {
            try {
                // Fetch all included sections ordered by position
                $sections = $documentImport->sections()
                    ->where('is_included', true)
                    ->orderBy('position')
                    ->get();

                if ($sections->isEmpty()) {
                    throw new RuntimeException('Không có phần nào được chọn để import.');
                }

                $sutraPosition = 1;
                $usedSlugs = [];
                // Map of import_section_id => created sutra_section_id
                $sectionIdMap = [];

                // Check if any headings exist
                $hasHeadings = $sections->contains(fn ($s) => $s->type->value === 'heading');

                if ($hasHeadings) {
                    // Structure with headings: group child paragraphs under headings
                    $headings = $sections->filter(fn ($s) => $s->type->value === 'heading');
                    $paragraphs = $sections->filter(fn ($s) => $s->type->value === 'paragraph');
                    $paragraphsByParent = $paragraphs->groupBy('parent_id');

                    // First pass: orphan paragraphs before any heading
                    $orphanParagraphs = $paragraphsByParent->get(null, collect());
                    foreach ($orphanParagraphs as $orphan) {
                        $title = $orphan->title ?: ('Phần '.$sutraPosition);
                        $slug = $this->generateUniqueSlug($title, $usedSlugs, $sutraPosition);

                        $created = SutraSection::create([
                            'sutra_id' => $sutra->id,
                            'sutra_version_id' => null,
                            'parent_id' => null,
                            'title' => $title,
                            'slug' => $slug,
                            'content' => $orphan->content,
                            'position' => $sutraPosition++,
                            'source_page_start' => $orphan->source_page_start,
                            'source_page_end' => $orphan->source_page_end,
                        ]);

                        $sectionIdMap[$orphan->id] = $created->id;
                    }

                    // Process headings
                    foreach ($headings as $heading) {
                        $title = $heading->title ?: ('Phần '.$sutraPosition);
                        $slug = $this->generateUniqueSlug($title, $usedSlugs, $sutraPosition);

                        // Gather child paragraphs text
                        $childParagraphs = $paragraphsByParent->get($heading->id, collect());
                        $paragraphsText = $childParagraphs->pluck('content')->filter()->implode("\n\n");

                        $finalContent = $heading->content ?: '';
                        if ($paragraphsText !== '') {
                            $finalContent = $finalContent !== ''
                                ? "{$finalContent}\n\n{$paragraphsText}"
                                : $paragraphsText;
                        }

                        // Determine parent SutraSection ID
                        $parentSutraSectionId = null;
                        if ($heading->parent_id && isset($sectionIdMap[$heading->parent_id])) {
                            $parentSutraSectionId = $sectionIdMap[$heading->parent_id];
                        }

                        $allPages = collect([
                            $heading->source_page_start,
                            $heading->source_page_end,
                            ...$childParagraphs->pluck('source_page_start')->all(),
                            ...$childParagraphs->pluck('source_page_end')->all(),
                        ])->filter();

                        $pageStart = $allPages->isNotEmpty() ? $allPages->min() : null;
                        $pageEnd = $allPages->isNotEmpty() ? $allPages->max() : null;

                        $created = SutraSection::create([
                            'sutra_id' => $sutra->id,
                            'sutra_version_id' => null,
                            'parent_id' => $parentSutraSectionId,
                            'title' => $title,
                            'slug' => $slug,
                            'content' => $finalContent !== '' ? $finalContent : null,
                            'position' => $sutraPosition++,
                            'source_page_start' => $pageStart,
                            'source_page_end' => $pageEnd,
                        ]);

                        $sectionIdMap[$heading->id] = $created->id;
                    }
                } else {
                    // Fallback when no headings: each paragraph becomes a SutraSection
                    foreach ($sections as $section) {
                        $title = $section->title ?: ('Đoạn '.$sutraPosition);
                        $slug = $this->generateUniqueSlug($title, $usedSlugs, $sutraPosition);

                        $created = SutraSection::create([
                            'sutra_id' => $sutra->id,
                            'sutra_version_id' => null,
                            'parent_id' => null,
                            'title' => $title,
                            'slug' => $slug,
                            'content' => $section->content,
                            'position' => $sutraPosition++,
                            'source_page_start' => $section->source_page_start,
                            'source_page_end' => $section->source_page_end,
                        ]);

                        $sectionIdMap[$section->id] = $created->id;
                    }
                }

                // 3. Update DocumentImport record
                $documentImport->update([
                    'sutra_id' => $sutra->id,
                    'sutra_version_id' => null,
                    'status' => DocumentImportStatus::Imported,
                    'processed_sections' => count($sectionIdMap),
                    'completed_at' => now(),
                    'error_message' => null,
                ]);

                Log::info('import succeeded', [
                    'document_import_id' => $documentImport->id,
                    'sutra_id' => $sutra->id,
                    'created_sections_count' => count($sectionIdMap),
                ]);

                return $sutra;
            } catch (Throwable $e) {
                Log::error('import failed', [
                    'document_import_id' => $documentImport->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Generate unique slug avoiding duplicates within the same version.
     *
     * @param  array<int, string>  $usedSlugs
     */
    private function generateUniqueSlug(string $title, array &$usedSlugs, int $position): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'phan-'.$position;
        }

        $slug = $base;
        $counter = 2;

        while (in_array($slug, $usedSlugs, true)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        $usedSlugs[] = $slug;

        return $slug;
    }
}

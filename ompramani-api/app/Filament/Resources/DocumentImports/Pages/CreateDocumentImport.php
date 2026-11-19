<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Enums\DocumentImportStatus;
use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Models\DocumentImport;
use App\Models\SourceDocument;
use App\Models\Sutra;
use App\Services\Documents\DocumentImportService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateDocumentImport extends CreateRecord
{
    protected static string $resource = DocumentImportResource::class;

    protected static ?string $title = 'Tải lên tài liệu (DOCX / PDF)';

    protected function handleRecordCreation(array $data): Model
    {
        $sutra = Sutra::with(['tradition', 'allSections'])->findOrFail($data['sutra_id']);

        // Guard against overwriting existing content
        if ($sutra->allSections()->exists()) {
            Notification::make()
                ->title('Kinh đã có nội dung')
                ->body("Kinh [{$sutra->title}] hiện đã có {$sutra->allSections()->count()} phần/chương. Để bảo toàn an toàn dữ liệu, hệ thống không tự động ghi đè.")
                ->danger()
                ->send();

            $this->halt();
        }

        $disk = (string) config('ompramani.import.disk', 'local');
        $storedPath = (string) ($data['docx_file'] ?? '');

        $storage = Storage::disk($disk);
        $fullPath = $storage->path($storedPath);

        $checksum = file_exists($fullPath) ? (hash_file('sha256', $fullPath) ?: '') : '';
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
        $originalFilename = $data['original_filename'] ?? basename($storedPath);

        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
        }

        $mimeType = $ext === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        $sourceDocument = SourceDocument::create([
            'original_filename' => $originalFilename,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'checksum' => $checksum,
            'source_name' => $sutra->source,
            'translator' => $sutra->translator,
            'publisher' => $sutra->publisher,
            'notes' => $sutra->notes,
            'uploaded_by' => auth()->id() ?? 1,
        ]);

        $documentImport = DocumentImport::create([
            'source_document_id' => $sourceDocument->id,
            'sutra_id' => $sutra->id,
            'sutra_version_id' => null,
            'version_name' => $sutra->title,
            'translator' => $sutra->translator,
            'language' => $sutra->language ?? 'vi',
            'publisher' => $sutra->publisher,
            'source' => $sutra->source,
            'notes' => $sutra->notes,
            'status' => DocumentImportStatus::Uploaded,
            'created_by' => auth()->id() ?? 1,
        ]);

        try {
            app(DocumentImportService::class)->process($documentImport);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Lỗi khi phân tích tài liệu')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $documentImport;
    }

    protected function getRedirectUrl(): string
    {
        return DocumentImportResource::getUrl('review', ['record' => $this->record]);
    }
}

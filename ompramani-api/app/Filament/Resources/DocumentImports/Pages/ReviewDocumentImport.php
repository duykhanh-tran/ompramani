<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Enums\DocumentImportStatus;
use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Models\DocumentImport;
use App\Services\Documents\DocumentImportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ReviewDocumentImport extends ViewRecord
{
    protected static string $resource = DocumentImportResource::class;

    protected static ?string $title = 'Review cấu trúc tài liệu';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import vào Kinh')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->visible(fn (DocumentImport $record): bool => $record->status === DocumentImportStatus::Review)
                ->requiresConfirmation()
                ->modalHeading('Xác nhận Import vào Kinh')
                ->modalDescription(function (DocumentImport $record): string {
                    $title = $record->sutra?->title ?? 'bộ Kinh đã chọn';

                    return "Hệ thống sẽ tạo các phần/chương SutraSection trực tiếp thuộc về Kinh [{$title}] từ các mục đã duyệt.";
                })
                ->modalSubmitActionLabel('Bắt đầu Import')
                ->action(function (DocumentImport $record): void {
                    try {
                        $sutra = app(DocumentImportService::class)->import($record);

                        Notification::make()
                            ->title('Import thành công!')
                            ->body("Đã nhập {$record->processed_sections} phần trực tiếp vào Kinh [{$sutra->title}].")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import thất bại')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('retry')
                ->label('Xử lý lại (Re-parse)')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->visible(fn (DocumentImport $record): bool => $record->status !== DocumentImportStatus::Imported)
                ->requiresConfirmation()
                ->modalHeading('Phân tích lại tệp nguồn')
                ->modalDescription('Hệ thống sẽ chạy lại parser để bóc tách các Heading và Paragraph từ tệp nguồn.')
                ->action(function (DocumentImport $record): void {
                    try {
                        app(DocumentImportService::class)->process($record);

                        Notification::make()
                            ->title('Phân tích hoàn tất')
                            ->body("Đã nhận diện {$record->total_sections} phần.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Phân tích thất bại')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

<?php

namespace App\Filament\Resources\DocumentImports\Tables;

use App\Enums\DocumentImportStatus;
use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Models\DocumentImport;
use App\Services\Documents\DocumentImportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentImportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sourceDocument.original_filename')
                    ->label('Tập tin')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('file_type')
                    ->label('Loại file')
                    ->state(function (DocumentImport $record): string {
                        $filename = $record->sourceDocument?->original_filename ?? '';
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        return $ext === 'pdf' ? 'PDF' : 'DOCX';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'PDF' ? 'danger' : 'info'),

                TextColumn::make('sutra.title')
                    ->label('Kinh mục tiêu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version_name')
                    ->label('Phiên bản')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (DocumentImportStatus $state): string => $state->color())
                    ->formatStateUsing(fn (DocumentImportStatus $state): string => $state->label())
                    ->sortable(),

                TextColumn::make('sections_summary')
                    ->label('Số phần')
                    ->state(fn (DocumentImport $record): string => "{$record->processed_sections} / {$record->total_sections}"),

                TextColumn::make('created_at')
                    ->label('Ngày tải lên')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Người tải lên')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('primary')
                    ->url(fn (DocumentImport $record): string => DocumentImportResource::getUrl('review', ['record' => $record])),

                Action::make('import')
                    ->label('Import')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->visible(fn (DocumentImport $record): bool => $record->status === DocumentImportStatus::Review)
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận Import vào Kinh')
                    ->modalDescription(function (DocumentImport $record): string {
                        $title = $record->sutra?->title ?? 'bộ Kinh đã chọn';

                        return "Hệ thống sẽ tạo các phần/chương SutraSection trực tiếp thuộc về Kinh [{$title}] từ các mục đã duyệt.";
                    })
                    ->modalSubmitActionLabel('Tiến hành Import')
                    ->action(function (DocumentImport $record): void {
                        try {
                            $sutra = app(DocumentImportService::class)->import($record);

                            Notification::make()
                                ->title('Import thành công!')
                                ->body("Đã nhập thành công {$record->processed_sections} phần trực tiếp vào Kinh [{$sutra->title}].")
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
                    ->label('Thử lại')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->visible(fn (DocumentImport $record): bool => $record->status === DocumentImportStatus::Failed)
                    ->requiresConfirmation()
                    ->modalHeading('Xử lý lại tập tin')
                    ->modalDescription('Hệ thống sẽ phân tích lại tệp tài liệu từ đầu.')
                    ->action(function (DocumentImport $record): void {
                        try {
                            app(DocumentImportService::class)->process($record);

                            Notification::make()
                                ->title('Phân tích hoàn tất')
                                ->body("Đã nhận diện {$record->total_sections} phần trong tài liệu.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Xử lý thất bại')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make(),
            ]);
    }
}

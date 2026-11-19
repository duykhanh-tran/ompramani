<?php

namespace App\Filament\Resources\DocumentImports;

use App\Filament\Resources\DocumentImports\Pages\CreateDocumentImport;
use App\Filament\Resources\DocumentImports\Pages\ListDocumentImports;
use App\Filament\Resources\DocumentImports\Pages\ReviewDocumentImport;
use App\Filament\Resources\DocumentImports\RelationManagers\ImportSectionsRelationManager;
use App\Filament\Resources\DocumentImports\Schemas\DocumentImportForm;
use App\Filament\Resources\DocumentImports\Tables\DocumentImportsTable;
use App\Models\DocumentImport;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DocumentImportResource extends Resource
{
    protected static ?string $model = DocumentImport::class;

    protected static string|UnitEnum|null $navigationGroup = 'Import Center';

    protected static ?string $modelLabel = 'Tài liệu nguồn';

    protected static ?string $pluralModelLabel = 'Document Imports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentImportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('sourceDocument.original_filename')
                    ->label('Tập tin gốc')
                    ->weight('bold'),

                TextEntry::make('sutra.title')
                    ->label('Kinh mục tiêu'),

                TextEntry::make('version_name')
                    ->label('Tên phiên bản dự kiến'),

                TextEntry::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label()),

                TextEntry::make('sections_count')
                    ->label('Tổng số phần đã nhận diện')
                    ->state(fn (DocumentImport $record): string => (string) $record->sections()->count()),

                TextEntry::make('created_at')
                    ->label('Thời gian tải lên')
                    ->dateTime('d/m/Y H:i:s'),

                TextEntry::make('error_message')
                    ->label('Chi tiết lỗi (nếu có)')
                    ->color('danger')
                    ->columnSpanFull()
                    ->visible(fn (DocumentImport $record): bool => ! empty($record->error_message)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return DocumentImportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImportSectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentImports::route('/'),
            'create' => CreateDocumentImport::route('/create'),
            'review' => ReviewDocumentImport::route('/{record}/review'),
            'view' => ReviewDocumentImport::route('/{record}'),
        ];
    }
}

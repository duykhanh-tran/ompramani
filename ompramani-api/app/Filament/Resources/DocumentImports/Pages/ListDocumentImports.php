<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentImports extends ListRecords
{
    protected static string $resource = DocumentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tải lên DOCX mới'),
        ];
    }
}

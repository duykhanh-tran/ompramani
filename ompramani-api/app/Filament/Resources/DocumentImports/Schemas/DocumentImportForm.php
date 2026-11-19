<?php

namespace App\Filament\Resources\DocumentImports\Schemas;

use App\Models\Sutra;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DocumentImportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('sutra_id')
                    ->label('Kinh mục tiêu')
                    ->relationship('sutra', 'title')
                    ->getOptionLabelFromRecordUsing(function (Sutra $record): string {
                        $tradition = $record->tradition?->name;

                        return $tradition ? "{$record->title} ({$tradition})" : $record->title;
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Chọn bộ Kinh mục tiêu để import nội dung văn bản trực tiếp.')
                    ->columnSpanFull(),

                FileUpload::make('docx_file')
                    ->label('Tập tin nguồn (DOCX / PDF)')
                    ->disk((string) config('ompramani.import.disk', 'local'))
                    ->directory((string) config('ompramani.import.directory', 'source_documents'))
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/pdf',
                    ])
                    ->maxSize((int) config('ompramani.import.max_file_size', 20480))
                    ->storeFileNamesIn('original_filename')
                    ->required()
                    ->helperText('Chấp nhận tập tin định dạng .docx hoặc .pdf (tối đa 20MB).')
                    ->columnSpanFull(),
            ]);
    }
}

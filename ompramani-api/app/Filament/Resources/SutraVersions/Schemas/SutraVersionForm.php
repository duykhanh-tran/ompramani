<?php

namespace App\Filament\Resources\SutraVersions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SutraVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sutra_id')
                    ->label('Kinh')
                    ->relationship('sutra', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Tên phiên bản')
                    ->required()
                    ->maxLength(255),

                TextInput::make('translator')
                    ->label('Dịch giả')
                    ->maxLength(255),

                Select::make('language')
                    ->label('Ngôn ngữ')
                    ->options([
                        'vi' => 'Tiếng Việt',
                        'zh' => 'Hán văn',
                        'pi' => 'Pali',
                        'sa' => 'Sanskrit',
                    ])
                    ->default('vi')
                    ->required(),

                TextInput::make('publisher')
                    ->label('Nhà xuất bản')
                    ->maxLength(255),

                TextInput::make('publication_year')
                    ->label('Năm xuất bản')
                    ->maxLength(255),

                Textarea::make('source')
                    ->label('Nguồn')
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->columnSpanFull(),

                Toggle::make('is_default')
                    ->label('Phiên bản mặc định'),

                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Bản nháp',
                        'review' => 'Chờ duyệt',
                        'published' => 'Đã xuất bản',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}

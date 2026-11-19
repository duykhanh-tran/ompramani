<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Hình ảnh & Bản quyền (Media & Images)';

    protected static ?string $modelLabel = 'Hình ảnh';

    protected static ?string $pluralModelLabel = 'Hình ảnh cơ sở';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('file_path')
                    ->label('Đường dẫn hình ảnh (URL / Storage Path)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('alt_text')
                    ->label('Văn bản thay thế (Alt text cho SEO & Trợ năng)')
                    ->maxLength(255),

                TextInput::make('caption')
                    ->label('Chú thích ảnh')
                    ->maxLength(255),

                TextInput::make('credit')
                    ->label('Nguồn ảnh / Tác giả / Đơn vị cung cấp')
                    ->maxLength(255),

                TextInput::make('license_info')
                    ->label('Thông tin giấy phép bản quyền (CC, Public Domain, etc.)')
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label('Thứ tự hiển thị')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Toggle::make('is_cover')
                    ->label('Đặt làm ảnh đại diện chính (Cover Image)')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_path')
                    ->label('Đường dẫn ảnh')
                    ->limit(35),

                TextColumn::make('alt_text')
                    ->label('Alt text')
                    ->limit(25),

                TextColumn::make('credit')
                    ->label('Tác quyền / Nguồn')
                    ->placeholder('-'),

                IconColumn::make('is_cover')
                    ->label('Ảnh bìa')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

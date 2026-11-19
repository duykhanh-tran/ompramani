<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecitationAudiosRelationManager extends RelationManager
{
    protected static string $relationship = 'recitationAudios';

    protected static ?string $title = 'Bản ghi âm tụng niệm (Audio Recitations)';

    protected static ?string $modelLabel = 'Bản ghi âm';

    protected static ?string $pluralModelLabel = 'Các bản ghi âm';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tiêu đề bản ghi âm')
                    ->placeholder('Vd: Tụng Chú Đại Bi - Nhịp truyền thống')
                    ->required()
                    ->maxLength(255),

                TextInput::make('file_path')
                    ->label('Đường dẫn file (Storage Path / URL)')
                    ->placeholder('audio/recitations/chu-dai-bi.mp3 hoặc https://...')
                    ->required()
                    ->maxLength(255),

                TextInput::make('file_url')
                    ->label('URL công khai trực tiếp (tùy chọn)')
                    ->placeholder('https://...')
                    ->maxLength(255),

                TextInput::make('duration_seconds')
                    ->label('Thời lượng (giây)')
                    ->numeric()
                    ->placeholder('320'),

                TextInput::make('performer')
                    ->label('Người tụng niệm / Đạo tràng thực hiện')
                    ->placeholder('Vd: Đạo tràng Tuệ Giác')
                    ->maxLength(255),

                TextInput::make('source')
                    ->label('Nguồn xuất xứ')
                    ->placeholder('Vd: Thu âm chính thức Ban Văn Hóa')
                    ->maxLength(255),

                TextInput::make('license_information')
                    ->label('Bản quyền / Giấy phép sử dụng')
                    ->placeholder('Vd: Đã được cấp quyền phát hành phi thương mại')
                    ->maxLength(255),

                Select::make('status')
                    ->label('Trạng thái duyệt')
                    ->options([
                        'draft' => 'Bản nháp (Chưa công khai)',
                        'published' => 'Đã duyệt xuất bản',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('performer')
                    ->label('Người tụng niệm')
                    ->placeholder('-'),

                TextColumn::make('duration_seconds')
                    ->label('Thời lượng')
                    ->formatStateUsing(fn (?int $state): string => $state ? floor($state / 60).'m '.($state % 60).'s' : '-')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Đã duyệt',
                        default => 'Bản nháp',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

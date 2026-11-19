<?php

namespace App\Filament\Resources\Mantras\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MantraVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Các phiên bản câu Chú';

    protected static ?string $modelLabel = 'Phiên bản Chú';

    protected static ?string $pluralModelLabel = 'Các phiên bản Chú';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên phiên bản')
                    ->placeholder('Vd: Phạn văn, Phiên âm Hán-Việt, Phiên âm Latin')
                    ->required()
                    ->maxLength(255),

                Select::make('language')
                    ->label('Ngôn ngữ')
                    ->options([
                        'sa' => 'Sanskrit (Phạn văn)',
                        'pi' => 'Pali',
                        'zh-Latn' => 'Phiên âm Hán-Việt',
                        'vi' => 'Tiếng Việt',
                        'zh' => 'Hán văn',
                        'en' => 'English',
                    ])
                    ->default('sa')
                    ->required(),

                TextInput::make('script')
                    ->label('Hệ chữ (Script)')
                    ->placeholder('latin, devanagari, siddham, han')
                    ->maxLength(30),

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

                TextInput::make('source')
                    ->label('Nguồn xuất xứ')
                    ->maxLength(255),

                Textarea::make('content')
                    ->label('Nội dung thần chú (Nguyên văn bảo toàn)')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),

                Textarea::make('transliteration')
                    ->label('Phiên âm / Hướng dẫn đọc tụng')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('translation')
                    ->label('Bản dịch nghĩa / Giải thích (nếu có nguồn xác thực)')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên phiên bản')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('language')
                    ->label('Ngôn ngữ')
                    ->badge(),

                TextColumn::make('script')
                    ->label('Hệ chữ')
                    ->placeholder('-'),

                IconColumn::make('is_default')
                    ->label('Mặc định')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Đã xuất bản',
                        'review' => 'Chờ duyệt',
                        default => 'Bản nháp',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'review' => 'warning',
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

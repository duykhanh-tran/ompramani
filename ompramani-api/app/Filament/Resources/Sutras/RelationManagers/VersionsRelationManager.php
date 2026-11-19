<?php

namespace App\Filament\Resources\Sutras\RelationManagers;

use App\Filament\Resources\SutraVersions\SutraVersionResource;
use App\Models\SutraVersion;
use Filament\Actions\Action;
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
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Các phiên bản kinh';

    protected static ?string $modelLabel = 'Phiên bản kinh';

    protected static ?string $pluralModelLabel = 'Các phiên bản kinh';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->label('Nhà xuất bản'),

                TextInput::make('publication_year')
                    ->label('Năm xuất bản'),

                Textarea::make('source')
                    ->label('Nguồn'),

                Textarea::make('notes')
                    ->label('Ghi chú'),

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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('translator')
                    ->placeholder('-'),
                TextEntry::make('language'),
                TextEntry::make('publisher')
                    ->placeholder('-'),
                TextEntry::make('publication_year')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_default')
                    ->boolean(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Phiên bản')
                    ->searchable(),

                TextColumn::make('translator')
                    ->label('Dịch giả'),

                TextColumn::make('language')
                    ->label('Ngôn ngữ'),

                IconColumn::make('is_default')
                    ->label('Mặc định')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('manage_sections')
                    ->label('Quản lý các phần')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->url(fn (SutraVersion $record): string => SutraVersionResource::getUrl('edit', ['record' => $record])),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

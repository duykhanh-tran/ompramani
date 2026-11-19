<?php

namespace App\Filament\Resources\SutraVersions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AllSectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'allSections';

    protected static ?string $title = 'Các phần / Mục';

    protected static ?string $modelLabel = 'Phần';

    protected static ?string $pluralModelLabel = 'Các phần';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tiêu đề')
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set, $get) => empty($get('slug')) && filled($state) ? $set('slug', Str::slug($state)) : null),

                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255),

                Select::make('parent_id')
                    ->label('Phần cha')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn (Builder $query, RelationManager $livewire) => $query->where('sutra_version_id', $livewire->getOwnerRecord()->getKey()),
                        ignoreRecord: true,
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->title ?: "Phần #{$record->position}")
                    ->searchable()
                    ->preload(),

                TextInput::make('position')
                    ->label('Thứ tự')
                    ->numeric()
                    ->default(0)
                    ->required(),

                TextInput::make('source_page_start')
                    ->label('Trang nguồn bắt đầu')
                    ->numeric(),

                TextInput::make('source_page_end')
                    ->label('Trang nguồn kết thúc')
                    ->numeric(),

                Textarea::make('content')
                    ->label('Nội dung')
                    ->rows(15)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('position')
                    ->label('Thứ tự'),
                TextEntry::make('title')
                    ->label('Tiêu đề')
                    ->placeholder('-'),
                TextEntry::make('slug')
                    ->placeholder('-'),
                TextEntry::make('parent.title')
                    ->label('Phần cha')
                    ->placeholder('-'),
                TextEntry::make('source_page_start')
                    ->label('Trang nguồn bắt đầu')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('source_page_end')
                    ->label('Trang nguồn kết thúc')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('content')
                    ->label('Nội dung')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.title')
                    ->label('Thuộc phần')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('source_page_start')
                    ->label('Trang đầu')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('source_page_end')
                    ->label('Trang cuối')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
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

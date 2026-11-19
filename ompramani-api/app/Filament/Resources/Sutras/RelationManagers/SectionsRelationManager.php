<?php

namespace App\Filament\Resources\Sutras\RelationManagers;

use App\Models\SutraSection;
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

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'allSections';

    protected static ?string $title = 'Các phẩm / Mục lục & Nội dung kinh';

    protected static ?string $modelLabel = 'Phẩm / Chương';

    protected static ?string $pluralModelLabel = 'Các phẩm / Chương';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('position')
                    ->label('Thứ tự hiển thị')
                    ->numeric()
                    ->default(1)
                    ->required(),

                TextInput::make('title')
                    ->label('Tiêu đề phẩm / chương')
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set, $get) => empty($get('slug')) && filled($state) ? $set('slug', Str::slug($state)) : null),

                TextInput::make('slug')
                    ->label('Đường dẫn tĩnh (Slug)')
                    ->maxLength(255),

                Select::make('parent_id')
                    ->label('Phẩm / Chương cha (nếu có)')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn (Builder $query, RelationManager $livewire) => $query->where('sutra_id', $livewire->getOwnerRecord()->getKey()),
                        ignoreRecord: true,
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->title ?: "Phần #{$record->position}")
                    ->searchable()
                    ->preload(),

                TextInput::make('source_page_start')
                    ->label('Trang nguồn bắt đầu')
                    ->numeric(),

                TextInput::make('source_page_end')
                    ->label('Trang nguồn kết thúc')
                    ->numeric(),

                Textarea::make('content')
                    ->label('Nội dung kinh văn')
                    ->rows(18)
                    ->columnSpanFull()
                    ->required(),
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
                    ->label('Phẩm cha')
                    ->placeholder('-'),
                TextEntry::make('source_page_start')
                    ->label('Trang bắt đầu')
                    ->placeholder('-'),
                TextEntry::make('source_page_end')
                    ->label('Trang kết thúc')
                    ->placeholder('-'),
                TextEntry::make('content')
                    ->label('Nội dung')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('position')
                    ->label('STT')
                    ->sortable()
                    ->width(60),

                TextColumn::make('title')
                    ->label('Tiêu đề phẩm / chương')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SutraSection $record): string => $record->parent ? "Thuộc: {$record->parent->title}" : 'Cấp chính'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('content')
                    ->label('Độ dài nội dung')
                    ->formatStateUsing(fn ($state): string => strlen((string) $state) > 0 ? number_format(strlen((string) $state)).' ký tự' : 'Trống')
                    ->badge()
                    ->color(fn ($state): string => strlen((string) $state) > 0 ? 'success' : 'danger'),

                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position', 'asc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Thêm phẩm mới')
                    ->mutateFormDataUsing(function (array $data): array {
                        $sutra = $this->getOwnerRecord();
                        $data['sutra_id'] = $sutra->id;
                        // Keep primaryVersion id for legacy db compatibility if exists
                        $primary = $sutra->primaryVersion();
                        if ($primary) {
                            $data['sutra_version_id'] = $primary->id;
                        }

                        return $data;
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\DocumentImports\RelationManagers;

use App\Enums\ImportSectionType;
use App\Models\ImportSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImportSectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Danh sách các phần nhận diện được (Review)';

    protected static ?string $modelLabel = 'Phần nội dung';

    protected static ?string $pluralModelLabel = 'Các phần nội dung';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Loại cấu trúc')
                    ->options([
                        'heading' => 'Tiêu đề (Heading)',
                        'paragraph' => 'Đoạn văn (Paragraph)',
                    ])
                    ->required(),

                TextInput::make('heading_level')
                    ->label('Cấp độ Heading (1-9)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(9),

                TextInput::make('title')
                    ->label('Tiêu đề phần')
                    ->maxLength(255),

                Select::make('parent_id')
                    ->label('Mục cha')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn (Builder $query, RelationManager $livewire) => $query->where('document_import_id', $livewire->getOwnerRecord()->getKey()),
                        ignoreRecord: true,
                    )
                    ->getOptionLabelFromRecordUsing(fn (ImportSection $record): string => $record->title ?: "Phần #{$record->position}")
                    ->searchable()
                    ->preload(),

                TextInput::make('position')
                    ->label('Thứ tự hiển thị')
                    ->numeric()
                    ->default(0)
                    ->required(),

                TextInput::make('source_page_start')
                    ->label('Trang bắt đầu')
                    ->numeric(),

                TextInput::make('source_page_end')
                    ->label('Trang kết thúc')
                    ->numeric(),

                Toggle::make('is_included')
                    ->label('Bao gồm trong import')
                    ->default(true),

                Textarea::make('content')
                    ->label('Nội dung văn bản')
                    ->rows(6)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position', 'asc')
            ->columns([
                TextColumn::make('position')
                    ->label('STT')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->color(fn (ImportSectionType $state): string => $state->color())
                    ->formatStateUsing(fn (ImportSectionType $state): string => $state->label()),

                TextColumn::make('heading_level')
                    ->label('Cấp')
                    ->formatStateUsing(fn (?int $state): string => $state ? "H{$state}" : '-')
                    ->alignCenter()
                    ->width('70px'),

                TextColumn::make('source_page')
                    ->label('Trang')
                    ->state(function (ImportSection $record): string {
                        if (! $record->source_page_start) {
                            return '-';
                        }
                        if ($record->source_page_end && $record->source_page_end !== $record->source_page_start) {
                            return "{$record->source_page_start}–{$record->source_page_end}";
                        }

                        return (string) $record->source_page_start;
                    })
                    ->alignCenter()
                    ->width('80px'),

                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->placeholder('(Không có tiêu đề)')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('content')
                    ->label('Trích đoạn nội dung')
                    ->limit(90)
                    ->wrap(),

                TextColumn::make('parent.title')
                    ->label('Mục cha')
                    ->placeholder('-'),

                IconColumn::make('is_included')
                    ->label('Chọn')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}

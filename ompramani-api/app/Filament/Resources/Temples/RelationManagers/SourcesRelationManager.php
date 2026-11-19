<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temples\RelationManagers;

use App\Enums\TempleSourceType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'sources';

    protected static ?string $title = 'Nguồn dữ liệu & Căn cứ xác minh (Sources)';

    protected static ?string $modelLabel = 'Nguồn kiểm chứng';

    protected static ?string $pluralModelLabel = 'Nguồn kiểm chứng';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source_title')
                    ->label('Tiêu đề tài liệu / Tên cơ quan ban hành')
                    ->required()
                    ->maxLength(255),

                TextInput::make('source_url')
                    ->label('Đường dẫn trực tuyến (URL nếu có)')
                    ->url()
                    ->maxLength(500),

                Select::make('source_type')
                    ->label('Loại hình nguồn dữ liệu')
                    ->options(collect(TempleSourceType::cases())->mapWithKeys(
                        fn (TempleSourceType $t): array => [$t->value => $t->label()]
                    )->all())
                    ->default(TempleSourceType::Other->value)
                    ->required(),

                Textarea::make('reference_note')
                    ->label('Trích yếu / Căn cứ / Ghi chú kiểm chứng')
                    ->rows(3),

                DateTimePicker::make('checked_at')
                    ->label('Thời điểm kiểm chứng')
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_title')
                    ->label('Tiêu đề nguồn')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('source_type')
                    ->label('Loại nguồn')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TempleSourceType ? $state->label() : (TempleSourceType::tryFrom((string) $state)?->label() ?? $state)),

                TextColumn::make('source_url')
                    ->label('Đường dẫn')
                    ->limit(30)
                    ->url(fn ($record) => $record->source_url, true),

                TextColumn::make('checked_at')
                    ->label('Ngày kiểm tra')
                    ->dateTime('d/m/Y H:i'),
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

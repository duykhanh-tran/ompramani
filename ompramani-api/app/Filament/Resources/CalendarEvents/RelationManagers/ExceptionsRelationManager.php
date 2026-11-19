<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalendarEvents\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    protected static ?string $title = 'Các ngoại lệ & Điều chỉnh theo năm (Exceptions & Overrides)';

    protected static ?string $modelLabel = 'Ngoại lệ sự kiện';

    protected static ?string $pluralModelLabel = 'Các ngoại lệ sự kiện';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('solar_year')
                    ->label('Năm Dương lịch áp dụng (Solar Year)')
                    ->numeric()
                    ->required()
                    ->placeholder('2026'),

                Select::make('action')
                    ->label('Hành động ngoại lệ')
                    ->options([
                        'skip' => 'Bỏ qua không tổ chức (skip)',
                        'move' => 'Dời sang ngày Dương lịch khác (move)',
                    ])
                    ->default('skip')
                    ->required()
                    ->live(),

                DatePicker::make('replacement_solar_date')
                    ->label('Ngày Dương lịch thay thế')
                    ->required(fn ($get): bool => $get('action') === 'move')
                    ->visible(fn ($get): bool => $get('action') === 'move'),

                Textarea::make('reason')
                    ->label('Lý do / Căn cứ văn bản Phật sự điều chỉnh')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_approved')
                    ->label('Đã được thẩm định & phê duyệt')
                    ->default(false),

                DateTimePicker::make('approved_at')
                    ->label('Thời điểm phê duyệt'),

                Select::make('approved_by')
                    ->label('Người phê duyệt')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('solar_year')
                    ->label('Năm')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Hành động')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'move' => 'Dời ngày (move)',
                        'skip' => 'Bỏ qua (skip)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'move' => 'warning',
                        'skip' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('replacement_solar_date')
                    ->label('Ngày thay thế')
                    ->date('d/m/Y')
                    ->placeholder('-'),

                IconColumn::make('is_approved')
                    ->label('Đã duyệt')
                    ->boolean(),

                TextColumn::make('reason')
                    ->label('Lý do')
                    ->limit(40),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
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

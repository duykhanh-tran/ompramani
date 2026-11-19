<?php

declare(strict_types=1);

namespace App\Filament\Resources\AlmanacMethods\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AlmanacMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('is_primary', 'desc')
            ->columns([
                TextColumn::make('key')
                    ->label('Mã phương pháp')
                    ->badge()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Tên phương pháp')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('version')
                    ->label('Phiên bản')
                    ->badge()
                    ->color('info'),

                TextColumn::make('source_title')
                    ->label('Nguồn tham chiếu')
                    ->limit(35),

                TextColumn::make('calendar_timezone')
                    ->label('Múi giờ')
                    ->badge(),

                IconColumn::make('is_primary')
                    ->label('Chính thức')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Hoạt động',
                        'inactive' => 'Tạm ngưng',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}

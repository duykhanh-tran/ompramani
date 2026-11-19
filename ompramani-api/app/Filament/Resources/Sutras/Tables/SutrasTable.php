<?php

namespace App\Filament\Resources\Sutras\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SutrasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Ảnh')
                    ->disk('public')
                    ->square()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Tên kinh')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->translator ? "Dịch giả: {$record->translator}" : null),
                TextColumn::make('tradition.name')
                    ->label('Truyền thống')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('translator')
                    ->label('Dịch giả')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('all_sections_count')
                    ->label('Số phẩm')
                    ->counts('allSections')
                    ->badge()
                    ->color('success'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Đã xuất bản',
                        'draft' => 'Bản nháp',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label('Ngày xuất bản')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

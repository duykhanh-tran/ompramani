<?php

namespace App\Filament\Resources\BuddhistFigures\Tables;

use App\Services\Media\MediaUrlResolver;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BuddhistFiguresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Ảnh')
                    ->circular()
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->state(fn ($record): ?string => MediaUrlResolver::resolve($record->thumbnail)),

                TextColumn::make('name')
                    ->label('Danh hiệu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('figure_type')
                    ->label('Phân loại')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'buddha' => 'Đức Phật',
                        'bodhisattva' => 'Bồ Tát',
                        'arahant' => 'A La Hán',
                        'teacher' => 'Tổ Sư / Thiền Sư',
                        default => 'Khác',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'buddha' => 'warning',
                        'bodhisattva' => 'success',
                        default => 'info',
                    }),

                TextColumn::make('original_name')
                    ->label('Tên gốc')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('tradition.name')
                    ->label('Truyền thống')
                    ->placeholder('-')
                    ->searchable(),

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

                TextColumn::make('published_at')
                    ->label('Ngày xuất bản')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('figure_type')
                    ->label('Phân loại')
                    ->options([
                        'buddha' => 'Đức Phật',
                        'bodhisattva' => 'Bồ Tát',
                        'arahant' => 'A La Hán',
                        'teacher' => 'Tổ Sư / Thiền Sư',
                        'other' => 'Khác',
                    ]),

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Bản nháp',
                        'review' => 'Chờ duyệt',
                        'published' => 'Đã xuất bản',
                    ]),
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

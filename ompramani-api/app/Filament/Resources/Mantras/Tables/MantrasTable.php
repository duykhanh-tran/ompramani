<?php

namespace App\Filament\Resources\Mantras\Tables;

use App\Models\Mantra;
use App\Services\Media\MediaUrlResolver;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MantrasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('figure.thumbnail')
                    ->label('Ảnh (Figure)')
                    ->circular()
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->state(fn ($record): ?string => MediaUrlResolver::resolve($record->figure?->thumbnail))
                    ->defaultImageUrl('https://lh3.googleusercontent.com/aida-public/AB6AXuA0yHXbepcasIOm7sdXStFD3jiTbEWkLEAuhYWHZoReJePvbIXopc820f28MkNX-wB7UZi4KzW1h5duIxv32pDxQIQCJs93zhGbWD2RuO41QWZZdoHgw9FUZ6G_5NLYvZHXMWde_BDeXdHmHhOmCpaNqKjG9fS9CESHLSlHBD4666w6Ib9scXz19o_ydlmVm8s5DqEMixxNsiVmq7vEIoiocrfJVJZOk9Jok8kDE6A'),

                TextColumn::make('title')
                    ->label('Tên Chú')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('figure.name')
                    ->label('Phật / Bồ Tát')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('original_text')
                    ->label('Chú gốc')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vietnamese_transcription')
                    ->label('Phiên âm')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Bản nháp',
                        'review' => 'Chờ duyệt',
                        'published' => 'Đã xuất bản',
                    ]),

                SelectFilter::make('buddhist_figure_id')
                    ->label('Phật / Bồ Tát')
                    ->relationship('figure', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('publish')
                    ->label('Xuất bản')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Mantra $record): bool => $record->status !== 'published')
                    ->action(function (Mantra $record): void {
                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                        ]);
                    }),
                Action::make('unpublish')
                    ->label('Gỡ xuất bản')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Mantra $record): bool => $record->status === 'published')
                    ->action(function (Mantra $record): void {
                        $record->update([
                            'status' => 'draft',
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

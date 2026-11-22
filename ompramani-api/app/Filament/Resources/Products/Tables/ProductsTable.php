<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Services\Media\MediaUrlResolver;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Ảnh')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->state(fn ($record): ?string => MediaUrlResolver::resolve($record->cover_image))
                    ->square()
                    ->defaultImageUrl('/images/product-placeholder.png'),

                TextColumn::make('name')
                    ->label('Tên vật phẩm')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('category.name')
                    ->label('Danh mục')
                    ->sortable()
                    ->badge(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('price_vnd')
                    ->label('Giá niêm yết')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((int) $state, 0, ',', '.').' ₫' : 'Tùy hỷ'),

                TextColumn::make('stock_quantity')
                    ->label('Tồn kho')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state) => $state instanceof ProductStatus ? $state->color() : (ProductStatus::tryFrom((string) $state)?->color() ?? 'gray'))
                    ->formatStateUsing(fn ($state) => $state instanceof ProductStatus ? $state->label() : (ProductStatus::tryFrom((string) $state)?->label() ?? $state)),

                IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean(),

                TextColumn::make('published_at')
                    ->label('Ngày phát hành')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Danh mục')
                    ->relationship('category', 'name'),

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(collect(ProductStatus::cases())->mapWithKeys(
                        fn (ProductStatus $s): array => [$s->value => $s->label()]
                    )->all()),

                TernaryFilter::make('is_active')
                    ->label('Kích hoạt'),
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

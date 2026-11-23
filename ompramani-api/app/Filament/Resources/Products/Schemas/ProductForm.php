<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\InventoryMode;
use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Danh mục vật phẩm')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Tên vật phẩm')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set, callable $get): void {
                        if ($operation === 'create' && filled($state) && empty($get('slug'))) {
                            $set('slug', Product::generateUniqueSlug((string) $state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Đường dẫn tĩnh (Slug URL)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Định danh URL duy nhất.'),

                TextInput::make('sku')
                    ->label('Mã định danh (SKU)')
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('Mã quản lý kho tùy chọn (ví dụ: TR-BO-108).'),

                TextInput::make('price_vnd')
                    ->label('Giá bán niêm yết (VNĐ)')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('₫')
                    ->helperText('Để trống nếu vật phẩm cúng dường / thỉnh tùy hỷ.'),

                Select::make('inventory_mode')
                    ->label('Chế độ tồn kho')
                    ->options(collect(InventoryMode::cases())->mapWithKeys(
                        fn (InventoryMode $m): array => [$m->value => $m->label()]
                    )->all())
                    ->default(InventoryMode::NotTracked->value)
                    ->required(),

                TextInput::make('stock_quantity')
                    ->label('Số lượng tồn kho')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Chỉ áp dụng khi bật chế độ theo dõi số lượng tồn kho.'),

                Select::make('status')
                    ->label('Trạng thái phát hành')
                    ->options(collect(ProductStatus::cases())->mapWithKeys(
                        fn (ProductStatus $s): array => [$s->value => $s->label()]
                    )->all())
                    ->default(ProductStatus::Draft->value)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        if ($state === ProductStatus::Published->value && empty($get('published_at'))) {
                            $set('published_at', now()->toDateTimeString());
                        }
                    }),

                DateTimePicker::make('published_at')
                    ->label('Thời điểm xuất bản')
                    ->helperText('Vật phẩm chỉ hiển thị công khai sau thời điểm này.'),

                Toggle::make('is_active')
                    ->label('Kích hoạt hiển thị')
                    ->default(true),

                FileUpload::make('cover_image')
                    ->label('Ảnh minh họa chính')
                    ->image()
                    ->disk((string) config('filesystems.public_disk', 'public'))
                    ->directory('products')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->maxSize(5120)
                    ->helperText('Định dạng hình ảnh JPG, PNG, WEBP (tối đa 5MB).'),

                Textarea::make('short_description')
                    ->label('Mô tả tóm tắt')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText('Tóm tắt chân thực, trang nhã. Không dùng luận điệu mê tín.'),

                Textarea::make('description')
                    ->label('Mô tả chi tiết và xuất xứ')
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('Nguồn gốc, chất liệu, kích thước và cách bảo quản chuẩn mực Phật giáo.'),
            ]);
    }
}

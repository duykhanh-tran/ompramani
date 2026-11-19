<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\InventoryMode;
use App\Enums\ProductStatus;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Shop\InventoryService;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ManageInventory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Cửa hàng';

    protected static ?string $navigationLabel = 'Quản Lý Kho';

    protected static ?string $title = 'Quản Lý Tồn Kho Vật Phẩm';

    protected static ?string $slug = 'quan-ly-kho';

    protected static ?int $navigationSort = 39;

    protected string $view = 'filament.pages.manage-inventory';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->with(['category']))
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Ảnh')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl('/images/product-placeholder.png'),

                TextColumn::make('name')
                    ->label('Tên vật phẩm')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('sku')
                    ->label('Mã SKU')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('category.name')
                    ->label('Danh mục')
                    ->badge()
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Số lượng tồn')
                    ->sortable()
                    ->formatStateUsing(function (Product $record): string {
                        if ($record->inventory_mode === InventoryMode::NotTracked) {
                            return '—';
                        }

                        return number_format($record->stock_quantity);
                    })
                    ->color(function (Product $record): string {
                        if ($record->inventory_mode === InventoryMode::NotTracked) {
                            return 'gray';
                        }

                        if ($record->stock_quantity <= 0) {
                            return 'danger';
                        }

                        if ($record->stock_quantity <= 5) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->weight('semibold'),

                TextColumn::make('stock_status')
                    ->label('Trạng thái kho')
                    ->badge()
                    ->state(function (Product $record): string {
                        if ($record->inventory_mode === InventoryMode::NotTracked) {
                            return 'Không theo dõi';
                        }

                        if ($record->stock_quantity <= 0) {
                            return 'Hết hàng';
                        }

                        if ($record->stock_quantity <= 5) {
                            return 'Sắp hết';
                        }

                        return 'Còn hàng';
                    })
                    ->color(function (Product $record): string {
                        if ($record->inventory_mode === InventoryMode::NotTracked) {
                            return 'gray';
                        }

                        if ($record->stock_quantity <= 0) {
                            return 'danger';
                        }

                        if ($record->stock_quantity <= 5) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('status')
                    ->label('Kinh doanh')
                    ->badge()
                    ->color(fn ($state) => $state instanceof ProductStatus ? $state->color() : (ProductStatus::tryFrom((string) $state)?->color() ?? 'gray'))
                    ->formatStateUsing(fn ($state) => $state instanceof ProductStatus ? $state->label() : (ProductStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
            ])
            ->defaultSort('stock_quantity', 'asc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Danh mục')
                    ->relationship('category', 'name'),

                SelectFilter::make('stock_level')
                    ->label('Tình trạng tồn kho')
                    ->options([
                        'in_stock' => 'Còn hàng (> 5)',
                        'low_stock' => 'Sắp hết (1 - 5)',
                        'out_of_stock' => 'Hết hàng (0)',
                        'not_tracked' => 'Không theo dõi',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'in_stock' => $query->where('inventory_mode', InventoryMode::Tracked->value)->where('stock_quantity', '>', 5),
                            'low_stock' => $query->where('inventory_mode', InventoryMode::Tracked->value)->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 5),
                            'out_of_stock' => $query->where('inventory_mode', InventoryMode::Tracked->value)->where('stock_quantity', '<=', 0),
                            'not_tracked' => $query->where('inventory_mode', InventoryMode::NotTracked->value),
                            default => $query,
                        };
                    }),

                TernaryFilter::make('is_active')
                    ->label('Đang kích hoạt'),
            ])
            ->actions([
                Action::make('adjust_stock')
                    ->label('Điều chỉnh kho')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (Product $record): bool => $record->inventory_mode === InventoryMode::Tracked)
                    ->modalHeading(fn (Product $record) => "Điều chỉnh tồn kho: {$record->name}")
                    ->modalDescription(fn (Product $record) => "Tồn kho sổ sách hiện tại: {$record->stock_quantity} vật phẩm.")
                    ->form([
                        Select::make('action_type')
                            ->label('Hình thức điều chỉnh')
                            ->options([
                                'add' => '1. Nhập thêm vật phẩm (+)',
                                'subtract' => '2. Xuất bớt vật phẩm (-)',
                                'set' => '3. Kiểm kê & Đặt lại số lượng (=)',
                            ])
                            ->default('add')
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Số lượng vật phẩm')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->helperText('Số lượng nguyên dương cần điều chỉnh.'),

                        Textarea::make('reason')
                            ->label('Lý do điều chỉnh kho (Bắt buộc để kiểm toán)')
                            ->required()
                            ->minLength(3)
                            ->rows(2)
                            ->placeholder('Ví dụ: Nhập bổ sung 50 bộ chuông mõ từ xưởng, hàng lỗi xuất hủy, kiểm kê kho định kỳ...'),
                    ])
                    ->action(function (Product $record, array $data, InventoryService $service): void {
                        $actor = auth()->user();
                        if (! $actor) {
                            Notification::make()->title('Không có quyền thực hiện')->danger()->send();

                            return;
                        }

                        $type = $data['action_type'] ?? 'add';
                        $quantity = (int) ($data['quantity'] ?? 0);
                        $reason = (string) ($data['reason'] ?? '');

                        try {
                            if ($type === 'add') {
                                $service->incrementStock($record, $quantity, $actor, $reason);
                            } elseif ($type === 'subtract') {
                                $service->decrementStock($record, $quantity, $actor, $reason);
                            } else {
                                $service->adjustStock($record, $quantity, $actor, $reason, 'stocktake');
                            }

                            $record->refresh();

                            Notification::make()
                                ->title('Điều chỉnh tồn kho thành công')
                                ->body("Tồn kho mới của '{$record->name}': {$record->stock_quantity} vật phẩm.")
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Lỗi điều chỉnh tồn kho')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_history')
                    ->label('Lịch sử kho')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn (Product $record) => "Lịch sử biến động kho: {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalContent(function (Product $record) {
                        $movements = InventoryMovement::where('product_id', $record->id)
                            ->with('actor')
                            ->orderBy('created_at', 'desc')
                            ->limit(15)
                            ->get();

                        return view('filament.pages.inventory-movement-modal', [
                            'product' => $record,
                            'movements' => $movements,
                        ]);
                    }),
            ]);
    }
}

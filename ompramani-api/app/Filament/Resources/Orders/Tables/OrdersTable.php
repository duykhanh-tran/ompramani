<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Services\Shop\OrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Mã đơn hàng')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Đã sao chép mã đơn hàng'),

                TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                TextColumn::make('customer_phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('placed_at')
                    ->label('Ngày đặt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('total_vnd')
                    ->label('Tổng thanh toán')
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' ₫'),

                TextColumn::make('order_status')
                    ->label('Trạng thái đơn')
                    ->badge()
                    ->color(fn ($state) => $state instanceof OrderStatus ? $state->color() : (OrderStatus::tryFrom((string) $state)?->color() ?? 'gray'))
                    ->formatStateUsing(fn ($state) => $state instanceof OrderStatus ? $state->label() : (OrderStatus::tryFrom((string) $state)?->label() ?? (string) $state)),

                TextColumn::make('payment_method')
                    ->label('Hình thức TT')
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : (PaymentMethod::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_status')
                    ->label('Thanh toán')
                    ->badge()
                    ->color(fn ($state) => $state instanceof PaymentStatus ? $state->color() : (PaymentStatus::tryFrom((string) $state)?->color() ?? 'gray'))
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : (PaymentStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
            ])
            ->defaultSort('placed_at', 'desc')
            ->filters([
                SelectFilter::make('order_status')
                    ->label('Trạng thái đơn hàng')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $s): array => [$s->value => $s->label()]
                    )->all()),

                SelectFilter::make('payment_status')
                    ->label('Trạng thái thanh toán')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $p): array => [$p->value => $p->label()]
                    )->all()),

                SelectFilter::make('payment_method')
                    ->label('Phương thức thanh toán')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m): array => [$m->value => $m->label()]
                    )->all()),
            ])
            ->actions([
                ViewAction::make(),

                // 1. Xác nhận đơn hàng (Pending -> Confirmed)
                Action::make('confirm')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Order $record): bool => $record->order_status === OrderStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận đơn hàng')
                    ->modalDescription(fn (Order $record) => "Quý quản trị viên có chắc chắn muốn xác nhận đơn hàng #{$record->order_number}?")
                    ->form([
                        Textarea::make('notes')
                            ->label('Ghi chú nội bộ (Tùy chọn)')
                            ->rows(2)
                            ->placeholder('Ví dụ: Đã gọi điện xác nhận với khách...'),
                    ])
                    ->action(function (Order $record, array $data, OrderWorkflowService $service): void {
                        $service->confirm($record, auth()->user(), $data['notes'] ?? null);
                        Notification::make()
                            ->title('Đã xác nhận đơn hàng thành công')
                            ->success()
                            ->send();
                    }),

                // 2. Bắt đầu chuẩn bị gói hàng (Confirmed -> Processing)
                Action::make('start_processing')
                    ->label('Chuẩn bị hàng')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(fn (Order $record): bool => $record->order_status === OrderStatus::Confirmed)
                    ->requiresConfirmation()
                    ->modalHeading('Bắt đầu xử lý & chuẩn bị vật phẩm')
                    ->modalDescription(fn (Order $record) => "Chuyển đơn hàng #{$record->order_number} sang trạng thái đang đóng gói?")
                    ->form([
                        Textarea::make('notes')
                            ->label('Ghi chú nội bộ (Tùy chọn)')
                            ->rows(2)
                            ->placeholder('Ví dụ: Đang lấy tràng hạt và kinh sách từ kho trang nghiêm...'),
                    ])
                    ->action(function (Order $record, array $data, OrderWorkflowService $service): void {
                        $service->startProcessing($record, auth()->user(), $data['notes'] ?? null);
                        Notification::make()
                            ->title('Đơn hàng đã chuyển sang trạng thái đang chuẩn bị')
                            ->success()
                            ->send();
                    }),

                // 3. Hoàn tất đơn hàng (Processing -> Completed)
                Action::make('complete')
                    ->label('Hoàn tất')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->order_status === OrderStatus::Processing)
                    ->requiresConfirmation()
                    ->modalHeading('Hoàn tất đơn hàng')
                    ->modalDescription(fn (Order $record) => "Xác nhận đơn hàng #{$record->order_number} đã hoàn thành bàn giao và giao nhận thành công?")
                    ->form([
                        Checkbox::make('cod_collected')
                            ->label('Xác nhận đã thu đủ tiền mặt COD từ bưu tá / đơn vị giao hàng')
                            ->default(true)
                            ->visible(fn (Order $record): bool => $record->payment_method === PaymentMethod::Cod && $record->payment_status !== PaymentStatus::Paid),

                        Textarea::make('notes')
                            ->label('Ghi chú nghiệm thu')
                            ->rows(2)
                            ->placeholder('Ví dụ: Bưu tá đã bàn giao nguyên vẹn cho khách...'),
                    ])
                    ->action(function (Order $record, array $data, OrderWorkflowService $service): void {
                        $codCollected = (bool) ($data['cod_collected'] ?? false);
                        $service->complete($record, auth()->user(), $data['notes'] ?? null, $codCollected);
                        Notification::make()
                            ->title('Đơn hàng đã hoàn tất')
                            ->success()
                            ->send();
                    }),

                // 4. Hủy đơn hàng (Pending / Confirmed -> Cancelled)
                Action::make('cancel')
                    ->label('Hủy đơn')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => in_array($record->order_status, [OrderStatus::Pending, OrderStatus::Confirmed], true))
                    ->requiresConfirmation()
                    ->modalHeading('Hủy đơn hàng')
                    ->modalDescription(fn (Order $record) => "Thao tác này sẽ hủy đơn hàng #{$record->order_number} và tự động hoàn trả số lượng tồn kho của các vật phẩm.")
                    ->form([
                        Select::make('reason')
                            ->label('Lý do hủy đơn')
                            ->options([
                                'customer_requested' => 'Khách hàng liên hệ yêu cầu hủy',
                                'out_of_stock' => 'Vật phẩm tạm thời hết hàng trong kho',
                                'unable_to_fulfill' => 'Không thể liên hệ được với người nhận',
                                'address_invalid' => 'Địa chỉ giao hàng không hợp lệ / ngoài vùng phục vụ',
                                'other' => 'Lý do khác',
                            ])
                            ->required()
                            ->default('customer_requested'),

                        Textarea::make('notes')
                            ->label('Chi tiết lý do / Ghi chú nội bộ')
                            ->rows(2)
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data, OrderWorkflowService $service): void {
                        $service->cancel($record, auth()->user(), 'admin', $data['reason'], $data['notes'] ?? null);
                        Notification::make()
                            ->title('Đã hủy đơn hàng và hoàn trả tồn kho')
                            ->warning()
                            ->send();
                    }),

                // 5. Xóa đơn hàng (Soft Delete) & Hoàn trả tồn kho nếu đang xử lý
                DeleteAction::make()
                    ->label('Xóa')
                    ->modalHeading('Xóa đơn hàng')
                    ->modalDescription(fn (Order $record) => "Bạn có chắc chắn muốn xóa đơn hàng #{$record->order_number}? Nếu đơn hàng chưa hoàn tất hoặc chưa hủy, số lượng tồn kho sẽ được tự động hoàn trả.")
                    ->before(function (Order $record): void {
                        if (in_array($record->order_status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true)) {
                            foreach ($record->items as $item) {
                                if ($item->product_id && $item->product) {
                                    $product = $item->product;
                                    if ($product->inventory_mode === InventoryMode::Tracked) {
                                        $product->increment('stock_quantity', $item->quantity);
                                        InventoryMovement::create([
                                            'product_id' => $product->id,
                                            'type' => 'order_cancelled',
                                            'quantity_before' => $product->stock_quantity - $item->quantity,
                                            'quantity_after' => $product->stock_quantity,
                                            'change' => $item->quantity,
                                            'reason' => "Hoàn trả tồn kho do xóa đơn hàng #{$record->order_number}",
                                            'actor_type' => 'admin',
                                            'actor_id' => auth()->id(),
                                            'created_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        }
                    })
                    ->successNotificationTitle('Đã xóa đơn hàng thành công'),
            ]);
    }
}

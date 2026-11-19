<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Services\Shop\OrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Xác nhận đơn hàng (Pending -> Confirmed)
            Action::make('confirm')
                ->label('Xác nhận đơn')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn (): bool => $this->getRecord()->order_status === OrderStatus::Pending)
                ->requiresConfirmation()
                ->modalHeading('Xác nhận đơn hàng')
                ->modalDescription(fn (): string => "Xác nhận đơn hàng #{$this->getRecord()->order_number}?")
                ->form([
                    Textarea::make('notes')
                        ->label('Ghi chú nội bộ')
                        ->rows(2)
                        ->placeholder('Ví dụ: Đã gọi điện xác nhận địa chỉ...'),
                ])
                ->action(function (array $data, OrderWorkflowService $service): void {
                    /** @var Order $order */
                    $order = $this->getRecord();
                    $service->confirm($order, auth()->user(), $data['notes'] ?? null);
                    Notification::make()->title('Đã xác nhận đơn hàng thành công')->success()->send();
                    $this->refreshFormData(['order_status', 'internal_notes']);
                }),

            // 2. Bắt đầu chuẩn bị gói hàng (Confirmed -> Processing)
            Action::make('start_processing')
                ->label('Chuẩn bị gói hàng')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn (): bool => $this->getRecord()->order_status === OrderStatus::Confirmed)
                ->requiresConfirmation()
                ->modalHeading('Bắt đầu xử lý & chuẩn bị vật phẩm')
                ->modalDescription(fn (): string => "Chuyển đơn hàng #{$this->getRecord()->order_number} sang trạng thái đóng gói?")
                ->form([
                    Textarea::make('notes')
                        ->label('Ghi chú nội bộ')
                        ->rows(2)
                        ->placeholder('Ví dụ: Đang đóng gói trang nghiêm...'),
                ])
                ->action(function (array $data, OrderWorkflowService $service): void {
                    /** @var Order $order */
                    $order = $this->getRecord();
                    $service->startProcessing($order, auth()->user(), $data['notes'] ?? null);
                    Notification::make()->title('Đơn hàng đã chuyển sang trạng thái đang chuẩn bị')->success()->send();
                    $this->refreshFormData(['order_status', 'internal_notes']);
                }),

            // 3. Hoàn tất đơn hàng (Processing -> Completed)
            Action::make('complete')
                ->label('Hoàn tất giao nhận')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->order_status === OrderStatus::Processing)
                ->requiresConfirmation()
                ->modalHeading('Hoàn tất đơn hàng')
                ->modalDescription(fn (): string => "Xác nhận đơn hàng #{$this->getRecord()->order_number} đã giao thành công?")
                ->form([
                    Checkbox::make('cod_collected')
                        ->label('Xác nhận đã thu đủ tiền mặt COD')
                        ->default(true)
                        ->visible(fn (): bool => $this->getRecord()->payment_method === PaymentMethod::Cod && $this->getRecord()->payment_status !== PaymentStatus::Paid),

                    Textarea::make('notes')
                        ->label('Ghi chú nghiệm thu')
                        ->rows(2)
                        ->placeholder('Ví dụ: Bưu tá đã bàn giao nguyên vẹn...'),
                ])
                ->action(function (array $data, OrderWorkflowService $service): void {
                    /** @var Order $order */
                    $order = $this->getRecord();
                    $codCollected = (bool) ($data['cod_collected'] ?? false);
                    $service->complete($order, auth()->user(), $data['notes'] ?? null, $codCollected);
                    Notification::make()->title('Đơn hàng đã hoàn tất')->success()->send();
                    $this->refreshFormData(['order_status', 'payment_status', 'completed_at', 'paid_at', 'internal_notes']);
                }),

            // 4. Hủy đơn hàng (Pending / Confirmed -> Cancelled)
            Action::make('cancel')
                ->label('Hủy đơn hàng')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->getRecord()->order_status, [OrderStatus::Pending, OrderStatus::Confirmed], true))
                ->requiresConfirmation()
                ->modalHeading('Hủy đơn hàng')
                ->modalDescription(fn (): string => "Hủy đơn hàng #{$this->getRecord()->order_number} và tự động hoàn trả số lượng tồn kho.")
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
                ->action(function (array $data, OrderWorkflowService $service): void {
                    /** @var Order $order */
                    $order = $this->getRecord();
                    $service->cancel($order, auth()->user(), 'admin', $data['reason'], $data['notes'] ?? null);
                    Notification::make()->title('Đã hủy đơn hàng và hoàn trả tồn kho')->warning()->send();
                    $this->refreshFormData(['order_status', 'cancelled_at', 'cancellation_reason', 'internal_notes']);
                }),

            // 5. Xóa đơn hàng (Soft Delete)
            DeleteAction::make()
                ->label('Xóa đơn')
                ->modalHeading('Xóa đơn hàng')
                ->modalDescription(fn (): string => "Bạn có chắc chắn muốn xóa đơn hàng #{$this->getRecord()->order_number}? Nếu đơn hàng chưa hoàn tất hoặc chưa hủy, số lượng tồn kho sẽ được tự động hoàn trả.")
                ->before(function (): void {
                    /** @var Order $record */
                    $record = $this->getRecord();
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
        ];
    }
}

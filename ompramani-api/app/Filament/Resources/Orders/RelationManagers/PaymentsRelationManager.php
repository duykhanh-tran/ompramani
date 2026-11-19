<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Services\Shop\SepayPaymentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Lịch sử thanh toán';

    protected static ?string $modelLabel = 'Giao dịch';

    protected static ?string $pluralModelLabel = 'Các lần thử thanh toán';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_ref')
            ->columns([
                TextColumn::make('attempt_number')
                    ->label('# Lần thử')
                    ->sortable(),

                TextColumn::make('transaction_ref')
                    ->label('Mã hóa đơn / Tham chiếu')
                    ->state(fn ($record): string => $record->transaction_ref ?? $record->vnp_txn_ref ?? '-')
                    ->copyable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('provider')
                    ->label('Cổng thanh toán')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'vnpay' => 'VNPAY (cũ)',
                        'sepay' => 'SePay (VietQR)',
                        'manual' => 'Thủ công',
                        'system' => 'Hệ thống',
                        default => strtoupper((string) $state),
                    }),

                TextColumn::make('bank_transaction_no')
                    ->label('Mã GD cổng')
                    ->state(fn ($record): string => $record->bank_transaction_no ?? $record->vnp_transaction_no ?? '-')
                    ->placeholder('Chưa có')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('amount_vnd')
                    ->label('Số tiền')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', '.').' ₫')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Cancelled => 'gray',
                        PaymentStatus::Expired => 'gray',
                        PaymentStatus::NeedsReconciliation => 'danger',
                    })
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),

                TextColumn::make('bank_code')
                    ->label('Ngân hàng / Kênh')
                    ->state(fn ($record): string => $record->bank_code ?? $record->vnp_bank_code ?? '-')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Thời gian khởi tạo')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Thời gian xác nhận')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('Chưa thanh toán')
                    ->sortable(),
            ])
            ->actions([
                Action::make('reconcile_sepay')
                    ->label('Đối soát SePay')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (OrderPayment $record): bool => $record->provider === 'sepay' && $record->status !== PaymentStatus::Paid)
                    ->requiresConfirmation()
                    ->modalHeading('Đối soát giao dịch với SePay')
                    ->modalDescription(fn (OrderPayment $record) => "Hệ thống sẽ kết nối trực tiếp đến SePay Gateway API để kiểm tra trạng thái thanh toán của hóa đơn #{$record->transaction_ref}.")
                    ->action(function (OrderPayment $record, SepayPaymentService $sepayService): void {
                        $invoice = (string) $record->transaction_ref;
                        $gatewayOrder = $sepayService->retrieveOrderFromGateway($invoice);

                        if (! $gatewayOrder) {
                            Notification::make()
                                ->title('Chưa tìm thấy giao dịch trên SePay')
                                ->body("Cổng SePay phản hồi chưa ghi nhận giao dịch cho hóa đơn {$invoice}. Trạng thái thanh toán không đổi.")
                                ->warning()
                                ->send();

                            return;
                        }

                        $gatewayStatus = strtoupper((string) ($gatewayOrder['order_status'] ?? $gatewayOrder['status'] ?? 'UNKNOWN'));

                        if (in_array($gatewayStatus, ['CAPTURED', 'PAID', 'APPROVED', 'SUCCESS'], true)) {
                            DB::transaction(function () use ($record, $gatewayOrder, $gatewayStatus): void {
                                $record->status = PaymentStatus::Paid;
                                $record->paid_at = now();
                                $record->bank_transaction_no = (string) ($gatewayOrder['id'] ?? $gatewayOrder['order_id'] ?? $record->bank_transaction_no);
                                $record->raw_payload = $gatewayOrder;
                                $record->save();

                                /** @var Order $order */
                                $order = $record->order;
                                if ($order) {
                                    $fromStatus = $order->order_status;
                                    $order->payment_status = PaymentStatus::Paid;
                                    $order->paid_at = now();
                                    if ($order->order_status === OrderStatus::Pending) {
                                        $order->order_status = OrderStatus::Confirmed;
                                    }
                                    $order->save();

                                    OrderStatusHistory::create([
                                        'order_id' => $order->id,
                                        'from_status' => $fromStatus,
                                        'to_status' => $order->order_status,
                                        'actor_type' => 'admin',
                                        'actor_id' => auth()->id(),
                                        'reason' => "Quản trị viên đối soát thành công qua SePay Gateway API (Trạng thái cổng: {$gatewayStatus})",
                                        'created_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Đối soát thành công!')
                                ->body("SePay xác nhận giao dịch {$invoice} đã hoàn tất ({$gatewayStatus}). Đơn hàng đã được chuyển sang Đã thanh toán.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Giao dịch chưa hoàn tất trên SePay')
                                ->body("Trạng thái hiện tại trên SePay: {$gatewayStatus}. Hệ thống giữ nguyên trạng thái đơn hàng.")
                                ->info()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('attempt_number', 'desc');
    }
}

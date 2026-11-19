<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->label('Mã đơn hàng')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('public_id')
                    ->label('Mã công khai (ULID)')
                    ->disabled()
                    ->dehydrated(false),

                DateTimePicker::make('placed_at')
                    ->label('Thời gian đặt hàng')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('order_status')
                    ->label('Trạng thái đơn hàng')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $s): array => [$s->value => $s->label()]
                    )->all())
                    ->disabled()
                    ->dehydrated(false),

                Select::make('payment_method')
                    ->label('Phương thức thanh toán')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m): array => [$m->value => $m->label()]
                    )->all())
                    ->disabled()
                    ->dehydrated(false),

                Select::make('payment_status')
                    ->label('Trạng thái thanh toán')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(
                        fn (PaymentStatus $p): array => [$p->value => $p->label()]
                    )->all())
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('customer_name')
                    ->label('Họ tên người nhận')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('customer_phone')
                    ->label('Số điện thoại')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('customer_email')
                    ->label('Email người nhận')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('shipping_province')
                    ->label('Tỉnh / Thành phố')
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('shipping_address')
                    ->label('Địa chỉ giao hàng')
                    ->rows(2)
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('subtotal_vnd')
                    ->label('Tiền hàng')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((int) $state, 0, ',', '.').' ₫' : '0 ₫')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('shipping_fee_vnd')
                    ->label('Cước vận chuyển')
                    ->formatStateUsing(fn ($state) => (int) $state === 0 ? 'Miễn phí' : number_format((int) $state, 0, ',', '.').' ₫')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('total_vnd')
                    ->label('Tổng thanh toán')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((int) $state, 0, ',', '.').' ₫' : '0 ₫')
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('notes')
                    ->label('Ghi chú của khách hàng')
                    ->rows(2)
                    ->disabled()
                    ->dehydrated(false),

                Textarea::make('internal_notes')
                    ->label('Ghi chú nội bộ Admin')
                    ->rows(3)
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('cancellation_reason')
                    ->label('Lý do hủy')
                    ->visible(fn ($record) => $record?->order_status === OrderStatus::Cancelled)
                    ->disabled()
                    ->dehydrated(false),

                DateTimePicker::make('cancelled_at')
                    ->label('Thời gian hủy')
                    ->visible(fn ($record) => $record?->order_status === OrderStatus::Cancelled)
                    ->disabled()
                    ->dehydrated(false),

                DateTimePicker::make('completed_at')
                    ->label('Thời gian hoàn thành')
                    ->visible(fn ($record) => $record?->order_status === OrderStatus::Completed)
                    ->disabled()
                    ->dehydrated(false),

                DateTimePicker::make('paid_at')
                    ->label('Thời gian thanh toán')
                    ->visible(fn ($record) => $record?->paid_at !== null)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}

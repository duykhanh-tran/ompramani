<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShopOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?OrderStatus $fromStatus,
        public OrderStatus $toStatus
    ) {}

    /**
     * Delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Array representation for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $orderNumber = $this->order->order_number;

        [$title, $message] = match ($this->toStatus) {
            OrderStatus::Confirmed => [
                "Đơn hàng {$orderNumber} đã được xác nhận",
                'OmPraMani đã xác nhận đơn hàng thỉnh vật phẩm tu học của quý đạo hữu. Cửa hàng sẽ sớm chuẩn bị gói hàng.',
            ],
            OrderStatus::Processing => [
                "Đơn hàng {$orderNumber} đang được chuẩn bị",
                'Vật phẩm tu học của quý đạo hữu đang được kiểm tra và đóng gói trang nghiêm trước khi gửi tới đơn vị vận chuyển.',
            ],
            OrderStatus::Completed => [
                "Đơn hàng {$orderNumber} đã hoàn tất",
                'Đơn hàng vật phẩm tu học của quý đạo hữu đã hoàn tất. Kính chúc quý đạo hữu thân tâm an lạc, vạn sự hanh thông!',
            ],
            OrderStatus::Cancelled => [
                "Đơn hàng {$orderNumber} đã được hủy",
                'Đơn hàng thỉnh vật phẩm của quý đạo hữu đã được hủy trên hệ thống theo quy định.',
            ],
            default => [
                "Cập nhật đơn hàng {$orderNumber}",
                "Trạng thái đơn hàng của quý đạo hữu đã được cập nhật sang: {$this->toStatus->label()}.",
            ],
        };

        return [
            'type' => 'shop_order',
            'category' => 'shop',
            'title' => $title,
            'message' => $message,
            'resource_type' => 'order',
            'resource_public_id' => $this->order->public_id,
            'order_number' => $orderNumber,
            'from_status' => $this->fromStatus?->value,
            'to_status' => $this->toStatus->value,
            'destination' => "/don-hang/{$this->order->public_id}",
            'created_at' => now()->toIso8601String(),
        ];
    }
}

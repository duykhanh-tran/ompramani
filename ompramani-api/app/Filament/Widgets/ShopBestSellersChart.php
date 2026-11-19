<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Pages\ShopRevenueReport;
use App\Models\OrderItem;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopBestSellersChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Sản Phẩm Bán Chạy (Top 5)';

    protected ?string $description = 'Biểu đồ xếp hạng theo số lượng bán hợp lệ';

    protected string $color = 'primary';

    protected ?string $maxHeight = '280px';

    protected ?string $emptyStateHeading = 'Chưa có sản phẩm phát sinh giao dịch hợp lệ.';

    protected ?string $emptyStateDescription = 'Chưa có dữ liệu vật phẩm nào được thanh toán trong kỳ thống kê này.';

    protected string|\BackedEnum|null $emptyStateIcon = Heroicon::OutlinedShoppingBag;

    public string $timePreset = '30days';

    public ?string $customFrom = null;

    public ?string $customTo = null;

    public function getHeading(): string|Htmlable|null
    {
        return 'Sản Phẩm Bán Chạy (Top 5)';
    }

    public function getDescription(): string|Htmlable|null
    {
        return 'Biểu đồ xếp hạng theo số lượng bán hợp lệ';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function isEmpty(): bool
    {
        $data = $this->getCachedData();
        $values = $data['datasets'][0]['data'] ?? [];

        return empty($values) || collect($values)->sum() == 0;
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return 'Đã bán: ' + context.raw + ' sản phẩm'; }",
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        [$startUtc, $endUtc] = ShopRevenueReport::resolveRange($this->timePreset, $this->customFrom, $this->customTo);

        $items = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereBetween('orders.paid_at', [$startUtc, $endUtc])
            ->whereNull('orders.deleted_at')
            ->select([
                'order_items.product_name_snapshot',
                DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
            ])
            ->groupBy([
                'order_items.product_name_snapshot',
            ])
            ->orderByDesc('total_quantity_sold')
            ->limit(5)
            ->get();

        $labels = [];
        $values = [];

        foreach ($items as $item) {
            $labels[] = Str::limit((string) $item->product_name_snapshot, 25);
            $values[] = (int) $item->total_quantity_sold;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Số lượng đã bán',
                    'data' => $values,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}

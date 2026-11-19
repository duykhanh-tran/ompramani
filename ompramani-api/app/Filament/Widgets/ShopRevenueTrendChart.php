<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Pages\ShopRevenueReport;
use App\Models\Order;
use App\Models\OrderRefund;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class ShopRevenueTrendChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Doanh Thu Theo Thời Gian';

    protected ?string $description = 'Dòng tiền thực thu theo ngày (VND)';

    protected string $color = 'warning';

    protected ?string $maxHeight = '280px';

    protected ?string $emptyStateHeading = 'Chưa có dữ liệu doanh thu trong khoảng thời gian này.';

    protected ?string $emptyStateDescription = 'Không phát sinh giao dịch thanh toán hợp lệ nào trong kỳ thống kê đã chọn.';

    protected string|\BackedEnum|null $emptyStateIcon = Heroicon::OutlinedChartBar;

    public string $timePreset = '30days';

    public ?string $customFrom = null;

    public ?string $customTo = null;

    public function getHeading(): string|Htmlable|null
    {
        return 'Doanh Thu Theo Thời Gian';
    }

    public function getDescription(): string|Htmlable|null
    {
        return 'Dòng tiền thực thu theo ngày (VND)';
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
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' ₫'; }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { if (value >= 1000000) return (value / 1000000) + 'M'; if (value >= 1000) return (value / 1000) + 'k'; return value; }",
                    ],
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        [$startUtc, $endUtc] = ShopRevenueReport::resolveRange($this->timePreset, $this->customFrom, $this->customTo);
        $tz = 'Asia/Ho_Chi_Minh';

        $orders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$startUtc, $endUtc])
            ->whereNull('deleted_at')
            ->select(['paid_at', 'total_vnd'])
            ->get();

        $refunds = OrderRefund::query()
            ->where('status', 'completed')
            ->whereBetween('refunded_at', [$startUtc, $endUtc])
            ->select(['refunded_at', 'amount_vnd'])
            ->get();

        $dailyMap = [];
        $cursor = $startUtc->copy()->setTimezone($tz)->startOfDay();
        $endLocal = $endUtc->copy()->setTimezone($tz)->endOfDay();

        while ($cursor->lte($endLocal)) {
            $key = $cursor->format('d/m');
            $dailyMap[$key] = 0;
            $cursor->addDay();
        }

        foreach ($orders as $order) {
            if ($order->paid_at) {
                $dayKey = $order->paid_at->timezone($tz)->format('d/m');
                if (isset($dailyMap[$dayKey])) {
                    $dailyMap[$dayKey] += (int) $order->total_vnd;
                }
            }
        }

        foreach ($refunds as $refund) {
            if ($refund->refunded_at) {
                $dayKey = $refund->refunded_at->timezone($tz)->format('d/m');
                if (isset($dailyMap[$dayKey])) {
                    $dailyMap[$dayKey] = max(0, $dailyMap[$dayKey] - (int) $refund->amount_vnd);
                }
            }
        }

        $labels = array_keys($dailyMap);
        $values = array_values($dailyMap);

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu (₫)',
                    'data' => $values,
                    'backgroundColor' => '#d97706',
                    'borderColor' => '#b45309',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ShopRevenueReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Cửa hàng';

    protected static ?string $navigationLabel = 'Thống Kê Doanh Thu & Bán Chạy';

    protected static ?string $title = 'Thống Kê Doanh Thu & Bán Chạy';

    protected static ?string $slug = 'thong-ke-doanh-thu-ban-chay';

    protected static ?int $navigationSort = 38;

    protected string $view = 'filament.pages.shop-revenue-report';

    /**
     * Active filter preset: 'today', '7days', '30days', 'custom'
     */
    public string $timePreset = '30days';

    public ?string $customFrom = null;

    public ?string $customTo = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function mount(): void
    {
        $this->customFrom = now('Asia/Ho_Chi_Minh')->subDays(29)->format('Y-m-d');
        $this->customTo = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
    }

    public function setPreset(string $preset): void
    {
        $this->timePreset = $preset;

        if ($preset === 'today') {
            $this->customFrom = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
            $this->customTo = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        } elseif ($preset === '7days') {
            $this->customFrom = now('Asia/Ho_Chi_Minh')->subDays(6)->format('Y-m-d');
            $this->customTo = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        } elseif ($preset === '30days') {
            $this->customFrom = now('Asia/Ho_Chi_Minh')->subDays(29)->format('Y-m-d');
            $this->customTo = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        }
    }

    /**
     * Resolve UTC date range from filter preset parameters.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public static function resolveRange(string $timePreset, ?string $customFrom = null, ?string $customTo = null): array
    {
        $tz = 'Asia/Ho_Chi_Minh';

        if ($timePreset === 'today') {
            $start = now($tz)->startOfDay();
            $end = now($tz)->endOfDay();
            $label = 'Hôm nay ('.now($tz)->format('d/m/Y').')';
        } elseif ($timePreset === '7days') {
            $start = now($tz)->subDays(6)->startOfDay();
            $end = now($tz)->endOfDay();
            $label = '7 ngày gần đây ('.$start->format('d/m').' - '.$end->format('d/m/Y').')';
        } elseif ($timePreset === 'custom' && $customFrom && $customTo) {
            $start = Carbon::parse($customFrom, $tz)->startOfDay();
            $end = Carbon::parse($customTo, $tz)->endOfDay();
            if ($start->gt($end)) {
                $temp = $start;
                $start = $end;
                $end = $temp;
            }
            $label = 'Tùy chọn ('.$start->format('d/m/Y').' - '.$end->format('d/m/Y').')';
        } else {
            // Default: 30 days
            $start = now($tz)->subDays(29)->startOfDay();
            $end = now($tz)->endOfDay();
            $label = '30 ngày gần đây ('.$start->format('d/m').' - '.$end->format('d/m/Y').')';
        }

        return [$start->copy()->setTimezone('UTC'), $end->copy()->setTimezone('UTC'), $label];
    }

    /**
     * Resolve UTC date range from active filter preset.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function getDateRange(): array
    {
        return self::resolveRange($this->timePreset, $this->customFrom, $this->customTo);
    }

    /**
     * Compute header revenue summary statistics: Only 2 main KPIs displayed on UI.
     * Refund logic is preserved and deducted from actual revenue.
     *
     * @return array{
     *   collected_revenue: int,
     *   refunded_amount: int,
     *   net_revenue: int,
     *   actual_revenue: int,
     *   paid_orders_count: int,
     *   date_label: string
     * }
     */
    public function getRevenueMetrics(): array
    {
        [$startUtc, $endUtc, $dateLabel] = $this->getDateRange();

        // 1. DOANH THU ĐÃ THU & SỐ ĐƠN: Tổng hợp trong một truy vấn duy nhất để giảm tải DB
        $orderMetrics = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$startUtc, $endUtc])
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(total_vnd), 0) as total_collected, COUNT(*) as paid_count')
            ->first();

        $collectedRevenue = (int) ($orderMetrics->total_collected ?? 0);
        $paidOrdersCount = (int) ($orderMetrics->paid_count ?? 0);

        // 2. TIỀN ĐÃ HOÀN: Khoản hoàn trả đã hoàn tất (completed) trong kỳ (xử lý nghiệp vụ, không hiển thị thẻ riêng)
        $refundedAmount = (int) OrderRefund::query()
            ->where('status', 'completed')
            ->whereBetween('refunded_at', [$startUtc, $endUtc])
            ->sum('amount_vnd');

        // 3. DOANH THU THỰC THU: Thu thực tế trừ tiền đã hoàn trong kỳ
        $actualRevenue = max(0, $collectedRevenue - $refundedAmount);

        return [
            'collected_revenue' => $collectedRevenue,
            'refunded_amount' => $refundedAmount,
            'net_revenue' => $actualRevenue,
            'actual_revenue' => $actualRevenue,
            'paid_orders_count' => $paidOrdersCount,
            'date_label' => $dateLabel,
        ];
    }

    /**
     * Daily revenue series for the Revenue Chart ("Doanh thu theo thời gian").
     * Properly deducts daily completed refunds to represent actual revenue.
     */
    public function getDailyRevenueData(): array
    {
        [$startUtc, $endUtc] = $this->getDateRange();
        $tz = 'Asia/Ho_Chi_Minh';

        // Query orders with paid_at in range
        $orders = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$startUtc, $endUtc])
            ->whereNull('deleted_at')
            ->select(['paid_at', 'total_vnd'])
            ->get();

        // Query completed refunds in range
        $refunds = OrderRefund::query()
            ->where('status', 'completed')
            ->whereBetween('refunded_at', [$startUtc, $endUtc])
            ->select(['refunded_at', 'amount_vnd'])
            ->get();

        // Map into days dictionary
        $dailyMap = [];
        $cursor = $startUtc->copy()->setTimezone($tz)->startOfDay();
        $endLocal = $endUtc->copy()->setTimezone($tz)->endOfDay();

        while ($cursor->lte($endLocal)) {
            $key = $cursor->format('d/m');
            $dailyMap[$key] = [
                'date' => $key,
                'full_date' => $cursor->format('d/m/Y'),
                'revenue' => 0,
                'orders_count' => 0,
            ];
            $cursor->addDay();
        }

        foreach ($orders as $order) {
            if ($order->paid_at) {
                $dayKey = $order->paid_at->timezone($tz)->format('d/m');
                if (isset($dailyMap[$dayKey])) {
                    $dailyMap[$dayKey]['revenue'] += (int) $order->total_vnd;
                    $dailyMap[$dayKey]['orders_count']++;
                }
            }
        }

        foreach ($refunds as $refund) {
            if ($refund->refunded_at) {
                $dayKey = $refund->refunded_at->timezone($tz)->format('d/m');
                if (isset($dailyMap[$dayKey])) {
                    $dailyMap[$dayKey]['revenue'] = max(0, $dailyMap[$dayKey]['revenue'] - (int) $refund->amount_vnd);
                }
            }
        }

        $series = array_values($dailyMap);
        $maxRevenue = collect($series)->max('revenue') ?: 1;

        return [
            'series' => $series,
            'max_revenue' => $maxRevenue,
            'total_in_chart' => collect($series)->sum('revenue'),
        ];
    }

    /**
     * Retrieve Top 10 Best Sellers based strictly on verified paid orders in the period.
     */
    public function getBestSellers(): Collection
    {
        [$startUtc, $endUtc] = $this->getDateRange();

        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereBetween('orders.paid_at', [$startUtc, $endUtc])
            ->whereNull('orders.deleted_at')
            ->select([
                'order_items.product_id',
                'order_items.product_name_snapshot',
                'order_items.product_sku_snapshot',
                'order_items.product_image_snapshot',
                DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                DB::raw('SUM(order_items.line_total_vnd) as total_revenue_vnd'),
            ])
            ->groupBy([
                'order_items.product_id',
                'order_items.product_name_snapshot',
                'order_items.product_sku_snapshot',
                'order_items.product_image_snapshot',
            ])
            ->orderByDesc('total_quantity_sold')
            ->orderByDesc('total_revenue_vnd')
            ->limit(10)
            ->get();
    }

    /**
     * Retrieve Top 5 Best Sellers formatted specifically for the Horizontal Bar Chart.
     */
    public function getTop5BestSellers(): Collection
    {
        $top5 = $this->getBestSellers()->take(5);
        $maxQuantity = (int) ($top5->max('total_quantity_sold') ?: 1);

        return $top5->map(function ($item) use ($maxQuantity) {
            $qty = (int) $item->total_quantity_sold;
            $item->bar_percentage = max(10, (int) round(($qty / $maxQuantity) * 100));

            return $item;
        });
    }
}

<x-filament-panels::page>
    @php
        $metrics = $this->getRevenueMetrics();
        $daily = $this->getDailyRevenueData();
        $top5BestSellers = $this->getTop5BestSellers();
        $allBestSellers = $this->getBestSellers();
    @endphp

    <style>
        .revenue-dashboard-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .revenue-filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .revenue-filter-buttons {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .revenue-custom-dates {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            border-top: 1px solid rgba(156, 163, 175, 0.2);
        }

        .revenue-date-field {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .revenue-date-label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }

        .dark .revenue-date-label {
            color: #9ca3af;
        }

        .revenue-tz-note {
            font-size: 0.6875rem;
            font-style: italic;
            color: #9ca3af;
        }

        .revenue-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .revenue-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .revenue-kpi-inner {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .revenue-kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .revenue-kpi-title {
            font-size: 0.8125rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }

        .dark .revenue-kpi-title {
            color: #9ca3af;
        }

        .revenue-kpi-badge {
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.625rem;
            font-weight: 700;
            font-size: 1.125rem;
        }

        .revenue-kpi-badge-amber {
            background-color: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }

        .dark .revenue-kpi-badge-amber {
            background-color: rgba(245, 158, 11, 0.25);
            color: #fbbf24;
        }

        .revenue-kpi-badge-blue {
            background-color: rgba(59, 130, 246, 0.15);
            color: #2563eb;
        }

        .dark .revenue-kpi-badge-blue {
            background-color: rgba(59, 130, 246, 0.25);
            color: #60a5fa;
        }

        .revenue-kpi-value {
            font-size: 2rem;
            line-height: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .revenue-kpi-amber {
            color: #d97706;
        }

        .dark .revenue-kpi-amber {
            color: #fbbf24;
        }

        .revenue-kpi-blue {
            color: #2563eb;
        }

        .dark .revenue-kpi-blue {
            color: #60a5fa;
        }

        .revenue-kpi-desc {
            font-size: 0.8125rem;
            line-height: 1.25rem;
            color: #6b7280;
        }

        .dark .revenue-kpi-desc {
            color: #9ca3af;
        }

        .revenue-table-wrapper {
            overflow-x: auto;
        }

        .revenue-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .revenue-th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            border-bottom: 1px solid rgba(156, 163, 175, 0.2);
        }

        .dark .revenue-th {
            color: #9ca3af;
        }

        .revenue-tr {
            border-bottom: 1px solid rgba(156, 163, 175, 0.1);
            transition: background-color 0.15s;
        }

        .revenue-tr:hover {
            background-color: rgba(156, 163, 175, 0.05);
        }

        .revenue-td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }

        .revenue-rank-badge {
            display: inline-block;
            padding: 0.125rem 0.375rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: rgba(156, 163, 175, 0.15);
        }

        .revenue-product-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .revenue-product-img {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            object-fit: cover;
        }

        .revenue-product-placeholder {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            background-color: rgba(156, 163, 175, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
        }

        .revenue-product-name {
            font-weight: 500;
        }

        .revenue-sku {
            font-size: 0.75rem;
            color: #6b7280;
            font-family: monospace;
        }

        .dark .revenue-sku {
            color: #9ca3af;
        }
    </style>

    <div class="revenue-dashboard-container">
        {{-- ======================================================== --}}
        {{-- PHẦN A: BỘ LỌC THỜI GIAN                                  --}}
        {{-- ======================================================== --}}
        <x-filament::section compact>
            <div class="revenue-filter-row">
                <div class="revenue-filter-buttons">
                    <x-filament::button
                        type="button"
                        wire:click="setPreset('today')"
                        :color="$timePreset === 'today' ? 'primary' : 'gray'"
                        size="sm"
                    >
                        Hôm nay
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        wire:click="setPreset('7days')"
                        :color="$timePreset === '7days' ? 'primary' : 'gray'"
                        size="sm"
                    >
                        7 ngày gần đây
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        wire:click="setPreset('30days')"
                        :color="$timePreset === '30days' ? 'primary' : 'gray'"
                        size="sm"
                    >
                        30 ngày gần đây
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        wire:click="setPreset('custom')"
                        :color="$timePreset === 'custom' ? 'primary' : 'gray'"
                        size="sm"
                    >
                        Khoảng ngày tùy chọn
                    </x-filament::button>
                </div>

                <div>
                    <x-filament::badge color="gray" size="lg">
                        Kỳ thống kê: {{ $metrics['date_label'] }}
                    </x-filament::badge>
                </div>
            </div>

            @if ($timePreset === 'custom')
                <div class="revenue-custom-dates">
                    <div class="revenue-date-field">
                        <span class="revenue-date-label">Từ ngày:</span>
                        <x-filament::input.wrapper size="sm">
                            <x-filament::input
                                type="date"
                                wire:model.live="customFrom"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="revenue-date-field">
                        <span class="revenue-date-label">Đến ngày:</span>
                        <x-filament::input.wrapper size="sm">
                            <x-filament::input
                                type="date"
                                wire:model.live="customTo"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <span class="revenue-tz-note">Múi giờ: Asia/Ho_Chi_Minh</span>
                </div>
            @endif
        </x-filament::section>

        {{-- ======================================================== --}}
        {{-- PHẦN B: 2 THẺ TỔNG QUAN CHÍNH (ĐÃ BỎ THẺ TIỀN ĐÃ HOÀN)    --}}
        {{-- ======================================================== --}}
        <div class="revenue-grid-2">
            {{-- Card 1: Doanh thu thực thu --}}
            <x-filament::section>
                <div class="revenue-kpi-inner">
                    <div class="revenue-kpi-header">
                        <span class="revenue-kpi-title">Doanh thu thực thu</span>
                        <span class="revenue-kpi-badge revenue-kpi-badge-amber">₫</span>
                    </div>
                    <div class="revenue-kpi-value revenue-kpi-amber">
                        {{ number_format($metrics['actual_revenue'], 0, ',', '.') }} ₫
                    </div>
                    <p class="revenue-kpi-desc">
                        Tổng số tiền thực tế đã thu, đã điều chỉnh theo các khoản hoàn tiền hoàn tất hợp lệ trong kỳ.
                    </p>
                </div>
            </x-filament::section>

            {{-- Card 2: Số đơn đã thanh toán --}}
            <x-filament::section>
                <div class="revenue-kpi-inner">
                    <div class="revenue-kpi-header">
                        <span class="revenue-kpi-title">Số đơn đã thanh toán</span>
                        <span class="revenue-kpi-badge revenue-kpi-badge-blue">✓</span>
                    </div>
                    <div class="revenue-kpi-value revenue-kpi-blue">
                        {{ number_format($metrics['paid_orders_count']) }} đơn
                    </div>
                    <p class="revenue-kpi-desc">
                        Đơn hàng có khoản thanh toán đã được xác nhận thực tế (COD đã thu hoặc chuyển khoản thành công).
                    </p>
                </div>
            </x-filament::section>
        </div>

        {{-- ======================================================== --}}
        {{-- PHẦN C & D: 2 BIỂU ĐỒ (FILAMENT CHART WIDGETS CHÍNH THỨC)   --}}
        {{-- ======================================================== --}}
        <div class="revenue-grid-2">
            {{-- Chart 1: Doanh Thu Theo Thời Gian --}}
            @livewire(\App\Filament\Widgets\ShopRevenueTrendChart::class, [
                'timePreset' => $timePreset,
                'customFrom' => $customFrom,
                'customTo' => $customTo,
            ], key('chart-trend-'.$timePreset.'-'.$customFrom.'-'.$customTo))

            {{-- Chart 2: Sản Phẩm Bán Chạy (Top 5) --}}
            @livewire(\App\Filament\Widgets\ShopBestSellersChart::class, [
                'timePreset' => $timePreset,
                'customFrom' => $customFrom,
                'customTo' => $customTo,
            ], key('chart-bestsellers-'.$timePreset.'-'.$customFrom.'-'.$customTo))
        </div>

        {{-- ======================================================== --}}
        {{-- PHẦN E: BẢNG CHI TIẾT TOP 10 SẢN PHẨM BÁN CHẠY            --}}
        {{-- ======================================================== --}}
        <x-filament::section
            heading="Chi Tiết Sản Phẩm Bán Chạy (Top 10)"
            description="Bảng thống kê số lượng và doanh thu snapshot theo các đơn hàng đã thanh toán hợp lệ"
        >
            @if ($allBestSellers->isEmpty())
                <x-filament::empty-state
                    :contained="false"
                    icon="heroicon-o-shopping-bag"
                    icon-color="gray"
                    heading="Chưa có dữ liệu vật phẩm"
                    description="Chưa có dữ liệu vật phẩm nào được thanh toán trong kỳ thống kê này."
                />
            @else
                <div class="revenue-table-wrapper">
                    <table class="revenue-table">
                        <thead>
                            <tr>
                                <th class="revenue-th" style="width: 60px;">#</th>
                                <th class="revenue-th">Vật phẩm</th>
                                <th class="revenue-th">Mã SKU</th>
                                <th class="revenue-th" style="text-align: right;">Đã bán</th>
                                <th class="revenue-th" style="text-align: right;">Doanh thu (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allBestSellers as $index => $item)
                                <tr class="revenue-tr">
                                    <td class="revenue-td">
                                        <span class="revenue-rank-badge">#{{ $index + 1 }}</span>
                                    </td>
                                    <td class="revenue-td">
                                        <div class="revenue-product-cell">
                                            @if ($item->product_image_snapshot)
                                                <img src="{{ asset('storage/' . $item->product_image_snapshot) }}" alt="{{ $item->product_name_snapshot }}" class="revenue-product-img" />
                                            @else
                                                <div class="revenue-product-placeholder">🪷</div>
                                            @endif
                                            <span class="revenue-product-name">{{ $item->product_name_snapshot }}</span>
                                        </div>
                                    </td>
                                    <td class="revenue-td revenue-sku">
                                        {{ $item->product_sku_snapshot ?: '—' }}
                                    </td>
                                    <td class="revenue-td" style="text-align: right; font-weight: 600;">
                                        {{ number_format($item->total_quantity_sold) }}
                                    </td>
                                    <td class="revenue-td" style="text-align: right; font-weight: 700; color: #d97706;">
                                        {{ number_format($item->total_revenue_vnd, 0, ',', '.') }} ₫
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>

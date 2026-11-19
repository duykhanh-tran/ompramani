<div class="space-y-4 text-sm">
    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
        <div>
            <span class="text-xs text-gray-500 uppercase tracking-wider block">Tồn kho hiện tại</span>
            <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                {{ $product->inventory_mode->value === 'tracked' ? number_format($product->stock_quantity).' vật phẩm' : 'Không theo dõi tồn kho' }}
            </span>
        </div>
        <div class="text-right">
            <span class="text-xs text-gray-500 uppercase tracking-wider block">Mã SKU</span>
            <span class="font-mono text-xs">{{ $product->sku ?: '—' }}</span>
        </div>
    </div>

    @if ($movements->isEmpty())
        <div class="p-6 text-center text-gray-500 italic bg-gray-50 dark:bg-gray-800 rounded-xl">
            Chưa có ghi nhận biến động kho nào cho vật phẩm này.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500">
                        <th class="py-2 px-2">Thời điểm</th>
                        <th class="py-2 px-2">Phân loại</th>
                        <th class="py-2 px-2 text-right">Trước</th>
                        <th class="py-2 px-2 text-right">Biến động</th>
                        <th class="py-2 px-2 text-right">Sau</th>
                        <th class="py-2 px-3">Lý do / Ghi chú</th>
                        <th class="py-2 px-2">Người thực hiện</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($movements as $m)
                        <tr>
                            <td class="py-2.5 px-2 text-gray-500 whitespace-nowrap">
                                {{ $m->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2.5 px-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    {{ $m->typeLabel() }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-right text-gray-500">
                                {{ number_format($m->quantity_before) }}
                            </td>
                            <td class="py-2.5 px-2 text-right font-bold {{ $m->change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $m->change > 0 ? '+'.number_format($m->change) : number_format($m->change) }}
                            </td>
                            <td class="py-2.5 px-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($m->quantity_after) }}
                            </td>
                            <td class="py-2.5 px-3 max-w-[200px] truncate text-gray-700 dark:text-gray-300" title="{{ $m->reason }}">
                                {{ $m->reason ?: '—' }}
                            </td>
                            <td class="py-2.5 px-2 text-gray-500 whitespace-nowrap">
                                {{ $m->actor?->name ?: ($m->actor_type === 'customer' ? 'Khách đặt' : 'Hệ thống') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
    <h2 class="text-lg font-semibold mb-3">Ringkasan Stok Saat Ini</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b bg-gray-50">
                <th class="py-3 px-3 text-left">Produk</th>
                <th class="py-3 px-3 text-left">SKU</th>
                <th class="py-3 px-3 text-left">Satuan</th>
                <th class="py-3 px-3 text-left">Stock Qty</th>
                <th class="py-3 px-3 text-left">Avg Cost</th>
                <th class="py-3 px-3 text-left">Stock Value</th>
                <th class="py-3 px-3 text-left">Expired Qty</th>
                <th class="py-3 px-3 text-left">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stockSummary as $row)
                @php
                    $current = (float) ($row->stock_qty ?? 0);
                    $minimum = (float) $row->minimum_stock;
                    $expiredQty = (float) ($row->expired_stock ?? 0);
                @endphp
                <tr class="border-b">
                    <td class="py-3 px-3">{{ $row->name }}</td>
                    <td class="py-3 px-3">{{ $row->sku }}</td>
                    <td class="py-3 px-3">{{ $row->unit?->symbol ?? '-' }}</td>
                    <td class="py-3 px-3">{{ number_format($current, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">{{ number_format((float) $row->average_cost, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">{{ number_format((float) $row->stock_value, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">{{ number_format($expiredQty, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">
                        @if ($current <= $minimum)
                            <span class="inline-flex rounded-full bg-red-100 text-red-700 px-2 py-1 text-xs font-medium">Low Stock</span>
                        @else
                            <span class="inline-flex rounded-full bg-green-100 text-green-700 px-2 py-1 text-xs font-medium">Aman</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-8 px-3 text-center text-gray-500">Belum ada data produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

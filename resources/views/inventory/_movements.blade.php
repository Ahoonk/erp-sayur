<div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
    <h2 class="text-lg font-semibold mb-3">Riwayat Pergerakan Stok</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b bg-gray-50">
                <th class="py-3 px-3 text-left">Tanggal</th>
                <th class="py-3 px-3 text-left">Produk</th>
                <th class="py-3 px-3 text-left">Batch</th>
                <th class="py-3 px-3 text-left">Kode</th>
                <th class="py-3 px-3 text-left">Masuk</th>
                <th class="py-3 px-3 text-left">Keluar</th>
                <th class="py-3 px-3 text-left">Unit Cost</th>
                <th class="py-3 px-3 text-left">Referensi</th>
                <th class="py-3 px-3 text-left">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $movement)
                @php
                    $code = strtolower((string) ($movement->movement_code ?? $movement->movement_type));
                    $badgeClass = match ($code) {
                        'in' => 'bg-green-100 text-green-700',
                        'out' => 'bg-blue-100 text-blue-700',
                        'reject' => 'bg-rose-100 text-rose-700',
                        'waste' => 'bg-red-100 text-red-700',
                        'expired' => 'bg-orange-100 text-orange-700',
                        default => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <tr class="border-b">
                    <td class="py-3 px-3">{{ optional($movement->moved_at)->format('d-m-Y H:i') ?? '-' }}</td>
                    <td class="py-3 px-3">{{ $movement->product?->name ?? '-' }}</td>
                    <td class="py-3 px-3">{{ $movement->batch?->batch_number ?? '-' }}</td>
                    <td class="py-3 px-3 uppercase">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badgeClass }}">{{ $code }}</span>
                    </td>
                    <td class="py-3 px-3">{{ number_format((float) $movement->qty_in, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">{{ number_format((float) $movement->qty_out, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">{{ number_format((float) $movement->unit_cost, 2, ',', '.') }}</td>
                    <td class="py-3 px-3">{{ $movement->reference_type ?? '-' }}</td>
                    <td class="py-3 px-3">{{ $movement->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="py-8 px-3 text-center text-gray-500">Belum ada transaksi stok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

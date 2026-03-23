@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Inventory Ledger</h1>
        <p class="text-sm text-gray-500 mt-1">Kartu stok transaksi IN/OUT/REJECT/WASTE/EXPIRED dengan running balance.</p>
    </div>

    @include('reports._tabs')

    <div class="bg-white rounded-xl shadow p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
            <div class="md:col-span-2">
                <label class="block mb-1">Produk</label>
                <select name="product_id" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Semua produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>
                            {{ $product->name }} ({{ $product->sku }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1">Tipe</label>
                <select name="movement_code" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Semua tipe</option>
                    @foreach (['in', 'out', 'reject', 'waste', 'expired'] as $code)
                        <option value="{{ $code }}" @selected(($filters['movement_code'] ?? '') === $code)>{{ strtoupper($code) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div class="md:col-span-5 flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Filter</button>
                <a href="{{ route('reports.ledger') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </div>

    @if ($is_limited)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
            Menampilkan maksimal 2.000 baris. Gunakan filter tanggal/produk agar data lebih fokus.
        </div>
    @endif

    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="py-3 px-3 text-left">Tanggal</th>
                    <th class="py-3 px-3 text-left">Produk</th>
                    <th class="py-3 px-3 text-left">Batch</th>
                    <th class="py-3 px-3 text-left">Tipe</th>
                    <th class="py-3 px-3 text-left">Masuk</th>
                    <th class="py-3 px-3 text-left">Keluar</th>
                    <th class="py-3 px-3 text-left">Saldo</th>
                    <th class="py-3 px-3 text-left">Unit Cost</th>
                    <th class="py-3 px-3 text-left">Nilai</th>
                    <th class="py-3 px-3 text-left">Ref</th>
                    <th class="py-3 px-3 text-left">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b">
                        <td class="py-3 px-3">{{ $row['moved_at']?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="py-3 px-3">{{ $row['product_name'] }}<div class="text-xs text-gray-500">{{ $row['sku'] }}</div></td>
                        <td class="py-3 px-3">{{ $row['batch_number'] ?? '-' }}</td>
                        <td class="py-3 px-3 uppercase">{{ $row['movement_code'] }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['qty_in'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['qty_out'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['running_balance'], 2, ',', '.') }} {{ $row['unit'] }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['unit_cost'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">Rp {{ number_format((float) $row['movement_value'], 0, ',', '.') }}</td>
                        <td class="py-3 px-3">{{ $row['reference_type'] ?? '-' }}</td>
                        <td class="py-3 px-3">{{ $row['notes'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-8 px-3 text-center text-gray-500">Belum ada data ledger sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

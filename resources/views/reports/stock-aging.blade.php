@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Stock Aging Report</h1>
        <p class="text-sm text-gray-500 mt-1">Pemantauan umur stok dan potensi expired per batch.</p>
    </div>

    @include('reports._tabs')

    <div class="bg-white rounded-xl shadow p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
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
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Filter</button>
                <a href="{{ route('reports.stock-aging') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        @php
            $buckets = ['0-30 hari', '31-60 hari', '61-90 hari', '> 90 hari'];
        @endphp
        @foreach ($buckets as $bucket)
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-xs text-gray-500">{{ $bucket }}</div>
                <div class="text-lg font-semibold text-gray-800">{{ number_format((float) ($summary[$bucket]['qty'] ?? 0), 2, ',', '.') }}</div>
                <div class="text-xs text-gray-500">Nilai: Rp {{ number_format((float) ($summary[$bucket]['value'] ?? 0), 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="py-3 px-3 text-left">Produk</th>
                    <th class="py-3 px-3 text-left">Batch</th>
                    <th class="py-3 px-3 text-left">Terima</th>
                    <th class="py-3 px-3 text-left">Expired</th>
                    <th class="py-3 px-3 text-left">Age (hari)</th>
                    <th class="py-3 px-3 text-left">Sisa ke Expired</th>
                    <th class="py-3 px-3 text-left">Qty</th>
                    <th class="py-3 px-3 text-left">Nilai</th>
                    <th class="py-3 px-3 text-left">Bucket</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b">
                        <td class="py-3 px-3">{{ $row['product_name'] }}<div class="text-xs text-gray-500">{{ $row['sku'] }}</div></td>
                        <td class="py-3 px-3">{{ $row['batch_number'] }}</td>
                        <td class="py-3 px-3">{{ $row['received_at']?->format('d-m-Y') ?? '-' }}</td>
                        <td class="py-3 px-3">{{ $row['expired_date']?->format('d-m-Y') ?? '-' }}</td>
                        <td class="py-3 px-3">{{ $row['age_days'] ?? '-' }}</td>
                        <td class="py-3 px-3">
                            @if (is_null($row['days_to_expire']))
                                -
                            @elseif ($row['days_to_expire'] < 0)
                                Lewat {{ abs($row['days_to_expire']) }} hari
                            @else
                                {{ $row['days_to_expire'] }} hari
                            @endif
                        </td>
                        <td class="py-3 px-3">{{ number_format((float) $row['remaining_qty'], 2, ',', '.') }} {{ $row['unit'] }}</td>
                        <td class="py-3 px-3">Rp {{ number_format((float) $row['remaining_value'], 0, ',', '.') }}</td>
                        <td class="py-3 px-3">{{ $row['aging_bucket'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 px-3 text-center text-gray-500">Belum ada data batch stok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

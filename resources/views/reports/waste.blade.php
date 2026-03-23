@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Waste Report</h1>
        <p class="text-sm text-gray-500 mt-1">Tracking waste, expired, dan reject supplier berdasarkan biaya aktual movement.</p>
    </div>

    @include('reports._tabs')

    <div class="bg-white rounded-xl shadow p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
            <div>
                <label class="block mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Filter</button>
                <a href="{{ route('reports.waste') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Waste</div>
            <div class="text-lg font-semibold text-red-700">{{ number_format((float) $summary['waste_qty'], 2, ',', '.') }}</div>
            <div class="text-xs text-gray-500">Rp {{ number_format((float) $summary['waste_cost'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Expired</div>
            <div class="text-lg font-semibold text-orange-700">{{ number_format((float) $summary['expired_qty'], 2, ',', '.') }}</div>
            <div class="text-xs text-gray-500">Rp {{ number_format((float) $summary['expired_cost'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Reject Supplier</div>
            <div class="text-lg font-semibold text-rose-700">{{ number_format((float) $summary['reject_qty'], 2, ',', '.') }}</div>
            <div class="text-xs text-gray-500">Rp {{ number_format((float) $summary['reject_cost'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="py-3 px-3 text-left">Produk</th>
                    <th class="py-3 px-3 text-left">Tipe</th>
                    <th class="py-3 px-3 text-left">Qty</th>
                    <th class="py-3 px-3 text-left">Nilai Biaya</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b">
                        <td class="py-3 px-3">{{ $row['product_name'] }}<div class="text-xs text-gray-500">{{ $row['sku'] }}</div></td>
                        <td class="py-3 px-3 uppercase">{{ $row['movement_code'] }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['total_qty'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">Rp {{ number_format((float) $row['total_cost'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 px-3 text-center text-gray-500">Belum ada transaksi waste/expired/reject.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

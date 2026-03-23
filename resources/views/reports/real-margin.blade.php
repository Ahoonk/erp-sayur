@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Real Margin Report</h1>
        <p class="text-sm text-gray-500 mt-1">Real HPP = (total purchase cost + waste cost) / qty sellable.</p>
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
                <a href="{{ route('reports.real-margin') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Purchase Cost</div>
            <div class="text-lg font-semibold text-gray-800">Rp {{ number_format((float) $summary['purchase_cost'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Revenue</div>
            <div class="text-lg font-semibold text-gray-800">Rp {{ number_format((float) $summary['revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Waste Cost</div>
            <div class="text-lg font-semibold text-red-700">Rp {{ number_format((float) $summary['waste_cost'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Reject Cost</div>
            <div class="text-lg font-semibold text-rose-700">Rp {{ number_format((float) $summary['reject_cost'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <div class="text-xs text-gray-500">Real Margin</div>
            <div class="text-lg font-semibold {{ (float) $summary['margin_value'] >= 0 ? 'text-green-700' : 'text-red-700' }}">Rp {{ number_format((float) $summary['margin_value'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="py-3 px-3 text-left">Produk</th>
                    <th class="py-3 px-3 text-left">Sold Qty</th>
                    <th class="py-3 px-3 text-left">Waste Qty</th>
                    <th class="py-3 px-3 text-left">Reject Qty</th>
                    <th class="py-3 px-3 text-left">Real HPP</th>
                    <th class="py-3 px-3 text-left">Revenue</th>
                    <th class="py-3 px-3 text-left">COGS</th>
                    <th class="py-3 px-3 text-left">Margin</th>
                    <th class="py-3 px-3 text-left">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b">
                        <td class="py-3 px-3">{{ $row['product_name'] }}<div class="text-xs text-gray-500">{{ $row['sku'] }}</div></td>
                        <td class="py-3 px-3">{{ number_format((float) $row['sold_qty'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['waste_qty'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">{{ number_format((float) $row['reject_qty'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">Rp {{ number_format((float) $row['real_hpp'], 2, ',', '.') }}</td>
                        <td class="py-3 px-3">Rp {{ number_format((float) $row['revenue'], 0, ',', '.') }}</td>
                        <td class="py-3 px-3">Rp {{ number_format((float) $row['cogs'], 0, ',', '.') }}</td>
                        <td class="py-3 px-3 {{ (float) $row['margin_value'] >= 0 ? 'text-green-700' : 'text-red-700' }}">Rp {{ number_format((float) $row['margin_value'], 0, ',', '.') }}</td>
                        <td class="py-3 px-3 {{ (float) $row['margin_percent'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format((float) $row['margin_percent'], 2, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 px-3 text-center text-gray-500">Belum ada transaksi yang bisa dihitung.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

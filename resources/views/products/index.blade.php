@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Products</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola master data produk untuk modul inventory dan transaksi.</p>
        </div>
        <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
            + Tambah Produk
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4 sm:p-6 space-y-4">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-col sm:flex-row gap-2">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                class="w-full sm:max-w-sm border rounded-lg px-3 py-2"
                placeholder="Cari nama atau SKU..."
            >
            <button type="submit" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                Cari
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 text-left">
                        <th class="py-3 px-3">SKU</th>
                        <th class="py-3 px-3">Nama</th>
                        <th class="py-3 px-3">Satuan</th>
                        <th class="py-3 px-3">Minimum Stok</th>
                        <th class="py-3 px-3">Modal Dasar Perusahaan</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b">
                            <td class="py-3 px-3 font-medium">{{ $product->sku }}</td>
                            <td class="py-3 px-3">{{ $product->name }}</td>
                            <td class="py-3 px-3">{{ $product->unit?->name ?? '-' }}{{ $product->unit?->symbol ? ' (' . $product->unit->symbol . ')' : '' }}</td>
                            <td class="py-3 px-3">{{ number_format((float) $product->minimum_stock, 2, ',', '.') }}</td>
                            <td class="py-3 px-3">
                                <form action="{{ route('products.update-selling-price', $product) }}" method="POST" class="flex items-center gap-2" x-data="{
                                    raw: @json((float) $product->selling_price),
                                    display: '',
                                    format(value) {
                                        const num = Number(value);
                                        if (!Number.isFinite(num)) return '';
                                        return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
                                    },
                                    toRaw(value) {
                                        if (!value) return '';
                                        return value.replace(/[^0-9,]/g, '').replace(',', '.');
                                    }
                                }" x-init="display = format(raw)">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center">
                                        <span class="border border-r-0 rounded-l-lg px-2.5 py-1.5 text-gray-600 bg-gray-50">Rp</span>
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            x-model="display"
                                            x-on:input="raw = toRaw(display)"
                                            x-on:blur="display = format(raw)"
                                            class="w-28 border rounded-r-lg px-3 py-1.5 text-right"
                                        >
                                        <input type="hidden" name="selling_price" x-model="raw">
                                    </div>
                                    <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-green-300 text-green-700 hover:bg-green-50" aria-label="Simpan modal dasar">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M4 10.5l3.5 3.5L16 6"></path>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-3">
                                @if ($product->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 text-green-700 px-2 py-1 text-xs font-medium">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-1 text-xs font-medium">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('products.edit', $product) }}" class="px-3 py-1.5 rounded-lg border border-blue-300 text-blue-700 hover:bg-blue-50">
                                        Edit
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 px-3 text-center text-gray-500">
                                Belum ada produk. Klik "Tambah Produk" untuk mulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="pt-2">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

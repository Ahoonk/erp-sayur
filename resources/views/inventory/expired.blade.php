@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    @include('inventory._alerts')

    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Stok Expired</h1>
        <p class="text-sm text-gray-500 mt-1">Qty expired hanya mengambil stok expired yang tersedia.</p>
    </div>

    @php($submitFormId = 'expired-form')
    @php($submitLabel = 'Catat Expired')
    @include('inventory._tabs')

    <div class="bg-white rounded-xl shadow p-5">
        <form id="expired-form" action="{{ route('inventory.expired') }}" method="POST" class="space-y-3 text-sm"
            x-data='{
                products: @json($productOptions),
                query: "",
                selectedId: @json(old("product_id")),
                unit: "",
                get filtered() {
                    if (!this.query) return this.products;
                    const q = this.query.toLowerCase();
                    return this.products.filter(p => (p.name + " " + p.sku).toLowerCase().includes(q));
                },
                updateUnit() {
                    const found = this.products.find(p => String(p.id) === String(this.selectedId));
                    this.unit = found ? found.unit : "";
                },
                init() { this.updateUnit(); }
            }'
            x-init="init()"
        >
            @csrf
            <div>
                <label class="block mb-1">Produk</label>
                <input
                    type="text"
                    placeholder="Cari produk..."
                    class="w-full border rounded-lg px-3 py-2 mb-2"
                    x-model="query"
                >
                <select name="product_id" class="w-full border rounded-lg px-3 py-2" required x-model="selectedId" x-on:change="updateUnit()">
                    <option value="">- Pilih produk -</option>
                    <template x-for="product in filtered" :key="product.id">
                        <option :value="product.id" x-text="`${product.name} (${product.sku})`"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block mb-1">Qty Expired</label>
                <div class="flex items-center">
                    <input type="number" step="0.01" min="0.01" name="quantity" class="no-spinner w-full border rounded-l-lg px-3 py-2" required>
                    <span class="border border-l-0 rounded-r-lg px-3 py-2 text-gray-600 bg-gray-50" x-text="unit || '-'"></span>
                </div>
            </div>
            <div>
                <label class="block mb-1">Keterangan</label>
                <input type="text" name="reason" class="w-full border rounded-lg px-3 py-2" placeholder="Opsional">
            </div>
        </form>
    </div>

    @include('inventory._summary')

    @include('inventory._movements')
</div>
@endsection

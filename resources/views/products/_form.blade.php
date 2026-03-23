@php
    $selectedCategory = old('category_id', isset($product) ? $product->category_id : '');
    $selectedUnit = old('unit_id', isset($product) ? $product->unit_id : '');
    $isActive = old('is_active', isset($product) ? (int) $product->is_active : 1);
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc ps-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
        <input
            id="sku"
            type="text"
            name="sku"
            value="{{ old('sku', isset($product) ? $product->sku : '') }}"
            required
            class="w-full border rounded-lg px-3 py-2 uppercase"
            placeholder="Contoh: BRG-001"
        >
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Produk</label>
        <input
            id="name"
            type="text"
            name="name"
            value="{{ old('name', isset($product) ? $product->name : '') }}"
            required
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Contoh: Beras Premium 25kg"
        >
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
        <select id="category_id" name="category_id" class="w-full border rounded-lg px-3 py-2">
            <option value="">- Pilih kategori -</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Atau isi kategori baru di bawah.</p>
        <input
            type="text"
            name="category_name"
            value="{{ old('category_name') }}"
            class="mt-2 w-full border rounded-lg px-3 py-2"
            placeholder="Kategori baru"
        >
    </div>

    <div>
        <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
        <select id="unit_id" name="unit_id" class="w-full border rounded-lg px-3 py-2">
            <option value="">- Pilih satuan -</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) $selectedUnit === (string) $unit->id)>
                    {{ $unit->name }} ({{ $unit->symbol }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Atau isi satuan baru di bawah.</p>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <input
                type="text"
                name="unit_name"
                value="{{ old('unit_name') }}"
                class="w-full border rounded-lg px-3 py-2"
                placeholder="Nama satuan"
            >
            <input
                type="text"
                name="unit_symbol"
                value="{{ old('unit_symbol') }}"
                class="w-full border rounded-lg px-3 py-2 uppercase"
                placeholder="Simbol (kg)"
            >
        </div>
    </div>

    <div>
        <label for="minimum_stock" class="block text-sm font-medium text-gray-700 mb-1">Minimum Stok</label>
        <input
            id="minimum_stock"
            type="number"
            name="minimum_stock"
            value="{{ old('minimum_stock', isset($product) ? (float) $product->minimum_stock : 0) }}"
            min="0"
            step="0.01"
            class="w-full border rounded-lg px-3 py-2"
        >
    </div>

    <div class="flex items-center gap-2 pt-7">
        <input
            id="is_active"
            type="checkbox"
            name="is_active"
            value="1"
            @checked((int) $isActive === 1)
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        >
        <label for="is_active" class="text-sm text-gray-700">Produk aktif</label>
    </div>
</div>

<div class="mt-6 flex items-center gap-2">
    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
        {{ $submitLabel ?? 'Simpan' }}
    </button>
    <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
        Batal
    </a>
</div>

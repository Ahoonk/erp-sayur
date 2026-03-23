<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('inventory.index') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('inventory.index') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Ringkasan</a>
        <a href="{{ route('inventory.stock-in.page') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('inventory.stock-in.page') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Stock In</a>
        <a href="{{ route('inventory.stock-out.page') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('inventory.stock-out.page') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Stock Out</a>
        <a href="{{ route('inventory.reject.page') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('inventory.reject.page') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Reject Supplier</a>
        <a href="{{ route('inventory.shrinkage.page') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('inventory.shrinkage.page') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Shrinkage</a>
        <a href="{{ route('inventory.expired.page') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('inventory.expired.page') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Expired</a>
    </div>
    @isset($submitFormId)
        <button type="submit" form="{{ $submitFormId }}" class="px-4 py-2 rounded-lg text-sm bg-blue-600 text-white hover:bg-blue-700">
            {{ $submitLabel ?? 'Simpan' }}
        </button>
    @endisset
</div>

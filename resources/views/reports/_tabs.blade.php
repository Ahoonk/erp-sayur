<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('reports.stock-aging') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('reports.stock-aging') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Stock Aging</a>
    <a href="{{ route('reports.real-margin') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('reports.real-margin') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Real Margin</a>
    <a href="{{ route('reports.waste') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('reports.waste') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Waste Report</a>
    <a href="{{ route('reports.ledger') }}" class="px-3 py-2 rounded-lg text-sm {{ request()->routeIs('reports.ledger') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Inventory Ledger</a>
</div>

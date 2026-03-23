@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    @include('inventory._alerts')

    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Ringkasan Inventory</h1>
        <p class="text-sm text-gray-500 mt-1">Stok masuk/keluar dipisah per halaman, pergerakan tetap tercatat FEFO + moving average cost.</p>
    </div>

    @include('inventory._tabs')

    @include('inventory._summary')

    @include('inventory._movements')
</div>
@endsection


@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Tambah Produk</h1>
            <p class="text-sm text-gray-500 mt-1">Lengkapi data produk untuk mulai transaksi inventory.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form action="{{ route('products.store') }}" method="POST" class="space-y-4">
            @csrf
            @include('products._form', ['submitLabel' => 'Simpan Produk'])
        </form>
    </div>
</div>
@endsection
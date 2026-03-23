@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Units</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola satuan produk master.</p>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Tambah Satuan</h2>
        <form action="{{ route('units.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-sm mb-1">Nama Satuan</label>
                <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2" placeholder="Contoh: Kilogram">
            </div>
            <div>
                <label class="block text-sm mb-1">Simbol</label>
                <input type="text" name="symbol" required class="w-full border rounded-lg px-3 py-2 uppercase" placeholder="KG">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="py-3 px-3 text-left">Nama</th>
                    <th class="py-3 px-3 text-left">Simbol</th>
                    <th class="py-3 px-3 text-left">Produk</th>
                    <th class="py-3 px-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr class="border-b">
                        <td class="py-3 px-3">
                            <form action="{{ route('units.update', $unit) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $unit->name }}" required class="w-full border rounded-lg px-3 py-2">
                        </td>
                        <td class="py-3 px-3">
                                <input type="text" name="symbol" value="{{ $unit->symbol }}" required class="w-24 border rounded-lg px-3 py-2 uppercase">
                        </td>
                        <td class="py-3 px-3">{{ $unit->products_count }}</td>
                        <td class="py-3 px-3">
                                <button type="submit" class="px-3 py-2 border rounded-lg text-blue-700 border-blue-300 hover:bg-blue-50">Update</button>
                            </form>
                            <form action="{{ route('units.destroy', $unit) }}" method="POST" class="mt-2" onsubmit="return confirm('Hapus satuan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 border rounded-lg text-red-700 border-red-300 hover:bg-red-50">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 px-3 text-center text-gray-500">Belum ada satuan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pt-4">{{ $units->links() }}</div>
    </div>
</div>
@endsection
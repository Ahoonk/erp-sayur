<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::query()
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return view('units.index', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:units,name'],
            'symbol' => ['required', 'string', 'max:20', 'unique:units,symbol'],
        ]);

        $validated['symbol'] = strtoupper($validated['symbol']);

        Unit::create($validated);

        return back()->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'name')->ignore($unit->id),
            ],
            'symbol' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'symbol')->ignore($unit->id),
            ],
        ]);

        $validated['symbol'] = strtoupper($validated['symbol']);

        $unit->update($validated);

        return back()->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'Satuan tidak bisa dihapus karena masih dipakai produk.');
        }

        $unit->delete();

        return back()->with('success', 'Satuan berhasil dihapus.');
    }
}
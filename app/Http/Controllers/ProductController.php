<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $filter) use ($search) {
                    $filter->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        $categories = ProductCategory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('products.create', compact('categories', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $categoryId = $this->resolveCategoryId($validated);
        $unitId = $this->resolveUnitId($validated);

        Product::create([
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'sku' => strtoupper((string) $validated['sku']),
            'name' => $validated['name'],
            'minimum_stock' => (float) ($validated['minimum_stock'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
            'selling_price' => (float) ($validated['selling_price'] ?? 0),
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'units'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);
        $categoryId = $this->resolveCategoryId($validated);
        $unitId = $this->resolveUnitId($validated);

        $product->update([
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'sku' => strtoupper((string) $validated['sku']),
            'name' => $validated['name'],
            'minimum_stock' => (float) ($validated['minimum_stock'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'selling_price' => (float) ($validated['selling_price'] ?? 0),
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function updateSellingPrice(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'selling_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $product->update([
            'selling_price' => (float) ($validated['selling_price'] ?? 0),
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Modal dasar perusahaan berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'required_without:category_name', Rule::exists('product_categories', 'id')],
            'category_name' => ['nullable', 'required_without:category_id', 'string', 'max:100'],
            'unit_id' => ['nullable', 'required_without:unit_name', Rule::exists('units', 'id')],
            'unit_name' => ['nullable', 'required_without:unit_id', 'string', 'max:100'],
            'unit_symbol' => ['nullable', 'required_with:unit_name', 'string', 'max:20'],
        ]);
    }

    private function resolveCategoryId(array $validated): int
    {
        if (!empty($validated['category_id'])) {
            return (int) $validated['category_id'];
        }

        $category = ProductCategory::firstOrCreate([
            'name' => trim((string) $validated['category_name']),
        ]);

        return $category->id;
    }

    private function resolveUnitId(array $validated): int
    {
        if (!empty($validated['unit_id'])) {
            return (int) $validated['unit_id'];
        }

        $unit = Unit::firstOrCreate(
            [
                'name' => trim((string) $validated['unit_name']),
            ],
            [
                'symbol' => strtoupper(trim((string) $validated['unit_symbol'])),
            ]
        );

        return $unit->id;
    }
}

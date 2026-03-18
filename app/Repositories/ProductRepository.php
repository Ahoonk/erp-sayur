<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function __construct(private Product $model) {}

    /**
     * Search products by exact barcode or IMEI (for scanner)
     */
    public function scan($code)
    {
        $product = $this->model->with(['masterProduct.brand', 'unit', 'grade'])
            ->inStock()
            ->scan($code)
            ->first();

        if (!$product) {
            return ResponseHelper::error('Product not found or out of stock', 404);
        }

        $availableStock = $this->resolveAvailableStock($product);

        return ResponseHelper::success([
            'id' => $product->id,
            'barcode' => $product->barcode,
            'nama' => $product->masterProduct->nama,
            'imei1' => $product->imei1,
            'imei2' => $product->imei2,
            'harga_jual' => (float) $product->harga_jual,
            'harga_modal' => (float) $product->harga_modal,
            'stok' => $availableStock,
            'brand' => $product->masterProduct->brand?->nama,
            'unit' => $product->unit ? ['nama' => $product->unit->nama] : null,
            'grade' => $product->grade ? ['nama' => $product->grade->nama] : null,
            'foto' => $product->foto,
        ], 'Product found');
    }

    /**
     * Search products by keyword (for text search autocomplete)
     */
    public function search($keyword, $category = null, $includeOutStock = false)
    {
        $query = $this->model->with(['masterProduct.brand', 'unit', 'grade'])
            ->when(!$includeOutStock, fn($q) => $q->inStock())
            ->search($keyword);

        $products = $query->take(10) // Limit results for autocomplete
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'master_product_id' => $product->master_product_id,
                    'barcode' => $product->barcode,
                    'nama' => $product->masterProduct->nama,
                    'imei1' => $product->imei1,
                    'imei2' => $product->imei2,
                    'harga_jual' => (float) $product->harga_jual,
                    'harga_modal' => (float) $product->harga_modal,
                    'stok' => $this->resolveAvailableStock($product),
                    'brand_id' => $product->masterProduct->brand_id,
                    'unit_id' => $product->unit_id,
                    'brand' => $product->masterProduct->brand?->nama,
                    'unit' => $product->unit ? ['nama' => $product->unit->nama] : null,
                    'grade' => $product->grade ? ['nama' => $product->grade->nama] : null,
                    'foto' => $product->foto,
                ];
            });

        return ResponseHelper::success($products, 'Products retrieved successfully');
    }

    public function groupedStock($perPage = 10, $search = null, $categoryId = null, $brandId = null)
    {
        $query = Product::query()
            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('brands', 'master_products.brand_id', '=', 'brands.id')
            ->leftJoin('grades', 'products.grade_id', '=', 'grades.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'products.master_product_id',
                'master_products.nama',
                'master_products.brand_id',
                'products.grade_id',
                'products.unit_id',
                DB::raw('MAX(products.harga_jual) as harga_jual'),
                DB::raw('SUM(products.stok) as total_stok'),
                'brands.nama as brand_nama',
                'grades.nama as grade_nama',
                'units.nama as unit_nama'
            )
            ->groupBy(
                'products.master_product_id',
                'master_products.nama',
                'master_products.brand_id',
                'products.grade_id',
                'products.unit_id',
                'brands.nama',
                'grades.nama',
                'units.nama'
            )
            ->having('total_stok', '>', 0)
            ->when($search, function ($q) use ($search) {
                $q->where('master_products.nama', 'like', "%{$search}%");
            })
            ->when($brandId, fn($q) => $q->where('master_products.brand_id', $brandId))
            ->orderBy('master_products.nama');

        $data = $query->paginate($perPage);

        // Transform data format for frontend consistency
        $data->getCollection()->transform(function ($item) {
            return [
                'master_product_id' => $item->master_product_id,
                'grade_id' => $item->grade_id,
                'nama' => $item->nama,
                'total_stok' => (int) $item->total_stok,
                'harga_jual' => (float) $item->harga_jual,
                'brand' => ['nama' => $item->brand_nama],
                'grade' => $item->grade_nama ? ['nama' => $item->grade_nama] : null,
                'unit' => $item->unit_nama ? ['nama' => $item->unit_nama] : null,
            ];
        });

        return ResponseHelper::success($data, 'Grouped stock retrieved successfully');
    }

    public function stockSummary($perPage = 10, $search = null, $brandId = null)
    {
        $query = \App\Models\MasterProduct::query()
            ->leftJoin('products', 'master_products.id', '=', 'products.master_product_id')
            ->leftJoin('brands', 'master_products.brand_id', '=', 'brands.id')
            ->select(
                'master_products.id as master_product_id',
                'master_products.nama',
                'master_products.brand_id',
                DB::raw('COALESCE(SUM(products.stok), 0) as total_stok'),
                'brands.nama as brand_nama'
            )
            ->groupBy(
                'master_products.id',
                'master_products.nama',
                'master_products.brand_id',
                'brands.nama'
            )
            ->when($search, function ($q) use ($search) {
                $q->where('master_products.nama', 'like', "%{$search}%");
            })
            ->when($brandId, fn($q) => $q->where('master_products.brand_id', $brandId))
            ->orderBy('master_products.nama');

        $data = $query->paginate($perPage);

        // Transform data format for frontend consistency
        $data->getCollection()->transform(function ($item) {
            return [
                'master_product_id' => $item->master_product_id,
                'nama' => $item->nama,
                'total_stok' => (int) $item->total_stok,
                'brand' => ['nama' => $item->brand_nama],
                'unit' => null,
            ];
        });

        return ResponseHelper::success($data, 'Stock summary retrieved successfully');
    }

    public function groupedStockDetails(string $masterProductId, ?string $gradeId = null)
    {
        $products = Product::query()
            ->with([
                'masterProduct.brand',
                'unit',
                'grade',
                'purchaseItems.purchase.supplier',
            ])
            ->where('master_product_id', $masterProductId)
            ->when($gradeId, fn($q) => $q->where('grade_id', $gradeId))
            ->where('stok', '>', 0)
            ->orderBy('created_at')
            ->get()
            ->map(function (Product $product) {
                $purchaseItem = $product->purchaseItems->sortByDesc('created_at')->first();
                $purchase = $purchaseItem?->purchase;

                return [
                    'id' => $product->id,
                    'nama' => $product->nama,
                    'barcode' => $product->barcode,
                    'imei1' => $product->imei1,
                    'imei2' => $product->imei2,
                    'stok' => (int) $product->stok,
                    'harga_modal' => (float) $product->harga_modal,
                    'harga_jual' => (float) $product->harga_jual,
                    'grade' => $product->grade?->nama,
                    'purchase_id' => $purchase?->id,
                    'invoice_pembelian' => $purchase?->no_invoice,
                    'supplier' => $purchase?->supplier?->nama,
                    'tanggal_pembelian' => optional($purchase?->tanggal)->format('Y-m-d'),
                    'satuan' => $product->unit?->nama,
                ];
            })
            ->values();

        return ResponseHelper::success($products, 'Grouped stock details retrieved successfully');
    }

    /**
     * Stock WA: master products with available stock, grouped by brand
     */
    public function stockWa(): \Illuminate\Http\JsonResponse
    {
        $rows = DB::table('products')
            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->join('brands', 'master_products.brand_id', '=', 'brands.id')
            ->whereNull('products.deleted_at')
            ->where('products.stok', '>', 0)
            ->select(
                'brands.nama as brand',
                'master_products.nama as nama',
                DB::raw('SUM(products.stok) as stok')
            )
            ->groupBy('brands.id', 'brands.nama', 'master_products.id', 'master_products.nama')
            ->orderBy('brands.nama')
            ->orderBy('master_products.nama')
            ->get();

        $grouped = $rows->groupBy('brand')->map(fn($items, $brand) => [
            'brand' => $brand,
            'products' => $items->map(fn($p) => [
                'nama' => $p->nama,
                'stok' => (int) $p->stok,
            ])->values(),
        ])->values();

        return ResponseHelper::success($grouped, 'Stock WA retrieved successfully');
    }

    private function resolveAvailableStock(Product $product): int
    {
        if (!empty($product->imei1) || !empty($product->imei2)) {
            return (int) $product->stok;
        }

        return (int) Product::query()
            ->where('master_product_id', $product->master_product_id)
            ->where('grade_id', $product->grade_id)
            ->where('unit_id', $product->unit_id)
            ->sum('stok');
    }
}

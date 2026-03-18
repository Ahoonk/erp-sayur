<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductRepository $repository) {}

    /**
     * Scan product by exact barcode, imei1, or imei2
     */
    public function scan(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        return $this->repository->scan($request->code);
    }

    /**
     * Search products by keyword (autocomplete)
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string',
            'keyword' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $keyword = $request->q ?? $request->keyword;

        return $this->repository->search(
            $keyword,
            $request->category,
            $request->boolean('include_out_stock')
        );
    }
    public function index(Request $request)
    {
        return $this->repository->groupedStock(
            $request->query('per_page', 10),
            $request->query('search'),
            $request->query('category_id'),
            $request->query('brand_id')
        );
    }

    public function summary(Request $request)
    {
        return $this->repository->stockSummary(
            $request->query('per_page', 10),
            $request->query('search'),
            $request->query('brand_id')
        );
    }

    public function stockDetails(Request $request)
    {
        $request->validate([
            'master_product_id' => 'required|uuid|exists:master_products,id',
            'grade_id' => 'nullable|uuid|exists:grades,id',
        ]);

        return $this->repository->groupedStockDetails(
            $request->query('master_product_id'),
            $request->query('grade_id')
        );
    }

    public function stockWa()
    {
        return $this->repository->stockWa();
    }
}

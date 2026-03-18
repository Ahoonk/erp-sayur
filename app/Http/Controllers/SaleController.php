<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\SaleStoreRequest;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\SaleItemAllocation;
use App\Repositories\SaleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SaleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view sales', only: ['index', 'show', 'stats']),
            new Middleware('permission:create sales', only: ['store']),
            new Middleware('permission:edit sales', only: ['update', 'updateItem']),
            new Middleware('permission:delete sales', only: ['destroy']),
        ];
    }

    public function __construct(private SaleRepository $repository) {}

    /**
     * Display a listing of sales
     */
    public function index(Request $request)
    {
        return $this->repository->index(
            $request->query('per_page', 10),
            $request->query('search'),
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('tipe'),
            $request->query('user_id'),
            $request->query('sales_rep_id')
        );
    }

    /**
     * Get sales statistics
     */
    public function stats(Request $request)
    {
        return $this->repository->stats($request->query('tipe'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaleStoreRequest $request)
    {
        return $this->repository->store($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return $this->repository->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaleStoreRequest $request, $id)
    {
        return $this->repository->update($id, $request->validated());
    }

    /**
     * Delete the specified resource.
     */
    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }

    /**
     * Update hpp_total for a specific sale item and sync to purchase items.
     */
    public function updateItem(Request $request, $saleId, $itemId)
    {
        $validated = $request->validate([
            'hpp_total' => ['required', 'numeric', 'min:0'],
        ]);

        $item = SaleItem::where('sales_transaction_id', $saleId)
            ->where('id', $itemId)
            ->with('allocations')
            ->firstOrFail();

        $newHppTotal = (float) $validated['hpp_total'];
        $item->update(['hpp_total' => $newHppTotal]);

        // Sync allocations and purchase items
        $allocations = $item->allocations;
        if ($allocations->isNotEmpty()) {
            $totalAllocQty = (int) $allocations->sum('qty') ?: 1;
            $newHargaModal = $newHppTotal / $totalAllocQty;

            foreach ($allocations as $allocation) {
                $allocation->update([
                    'harga_modal'   => $newHargaModal,
                    'subtotal_hpp'  => $newHargaModal * $allocation->qty,
                ]);
            }

            // Update only the edited sale item's product, so modal sync stays on the same row/code.
            $productIds = collect([$item->product_id])->filter();
            foreach ($productIds as $productId) {
                Product::where('id', $productId)->update(['harga_modal' => $newHargaModal]);

                $purchaseItems = PurchaseItem::where('product_id', $productId)->get();
                foreach ($purchaseItems as $purchaseItem) {
                    $purchaseItem->update([
                        'harga_beli' => $newHargaModal,
                        'subtotal'   => $newHargaModal * $purchaseItem->qty,
                    ]);
                    $purchaseItem->purchase->recalculateTotal();
                }
            }
        }

        return ResponseHelper::success($item, 'HPP berhasil diperbarui');
    }
}

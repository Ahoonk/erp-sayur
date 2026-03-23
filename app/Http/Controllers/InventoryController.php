<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(): View
    {
        $products = $this->getProductsReference();

        $movements = StockMovement::query()
            ->with(['product.unit', 'batch'])
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('inventory.index', [
            'products' => $products,
            'stockSummary' => $products,
            'movements' => $movements,
        ]);
    }

    public function stockInPage(): View
    {
        return $this->renderTransactionPage('inventory.stock-in');
    }

    public function stockOutPage(): View
    {
        return $this->renderTransactionPage('inventory.stock-out');
    }

    public function stockRejectPage(): View
    {
        return $this->renderTransactionPage('inventory.reject');
    }

    public function stockShrinkagePage(): View
    {
        return $this->renderTransactionPage('inventory.shrinkage');
    }

    public function stockExpiredPage(): View
    {
        return $this->renderTransactionPage('inventory.expired');
    }

    public function stockIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'expired_date' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $batch = $this->inventoryService->addStock((int) $validated['product_id'], [
                'quantity' => (float) $validated['quantity'],
                'purchase_price' => (float) ($validated['purchase_price'] ?? 0),
                'expired_date' => $validated['expired_date'] ?? null,
                'reference_type' => 'manual_in',
                'notes' => $validated['notes'] ?? null,
                'received_at' => $validated['received_at'] ?? now(),
            ]);
        } catch (Throwable $exception) {
            return back()
                ->with('error', 'Stock In gagal: ' . $exception->getMessage())
                ->withInput();
        }

        return back()->with('success', 'Stok masuk berhasil dicatat. Batch: ' . $batch->batch_number);
    }

    public function stockOut(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->processStockRemoval(
            (int) $validated['product_id'],
            (float) $validated['quantity'],
            'out',
            [
                'reference_type' => 'manual_out',
                'notes' => $validated['notes'] ?? null,
                'moved_at' => now(),
            ],
            'Stok keluar berhasil dicatat.'
        );
    }

    public function stockReject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        return $this->processStockRemoval(
            (int) $validated['product_id'],
            (float) $validated['quantity'],
            'reject',
            [
                'reference_type' => 'reject_supplier',
                'notes' => 'Reject supplier: ' . $validated['reason'],
                'moved_at' => now(),
            ],
            'Reject supplier berhasil dicatat.'
        );
    }

    public function stockWaste(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        return $this->processStockRemoval(
            (int) $validated['product_id'],
            (float) $validated['quantity'],
            'waste',
            [
                'reference_type' => 'waste',
                'notes' => 'Waste/Shrinkage: ' . $validated['reason'],
                'moved_at' => now(),
            ],
            'Waste/shrinkage berhasil dicatat.'
        );
    }

    public function stockExpired(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        return $this->processStockRemoval(
            (int) $validated['product_id'],
            (float) $validated['quantity'],
            'expired',
            [
                'reference_type' => 'expired',
                'notes' => 'Expired: ' . ($validated['reason'] ?? 'Auto expired deduction'),
                'moved_at' => now(),
                'expired_only' => true,
            ],
            'Stok expired berhasil dicatat.'
        );
    }

    public function stockShrinkage(Request $request): RedirectResponse
    {
        return $this->stockWaste($request);
    }

    private function renderTransactionPage(string $view): View
    {
        $products = $this->getProductsReference();
        $productOptions = $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit?->symbol ?? $product->unit?->name ?? '',
        ]);

        $movements = StockMovement::query()
            ->with(['product.unit', 'batch'])
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view($view, [
            'products' => $products,
            'stockSummary' => $products,
            'productOptions' => $productOptions,
            'movements' => $movements,
        ]);
    }

    private function getProductsReference()
    {
        return Product::query()
            ->with('unit')
            ->withSum(['batches as expired_stock' => function ($query) {
                $query->where('remaining_quantity', '>', 0)
                    ->whereNotNull('expired_date')
                    ->whereDate('expired_date', '<=', now()->toDateString());
            }], 'remaining_quantity')
            ->orderBy('name')
            ->get();
    }

    private function processStockRemoval(
        int $productId,
        float $quantity,
        string $movementCode,
        array $meta,
        string $successMessage
    ): RedirectResponse {
        $product = Product::query()->findOrFail($productId);
        $availableStock = (float) $product->stock_qty;


        if ($quantity - $availableStock > 0.0001) {
            return back()
                ->with('error', 'Qty melebihi stok tersedia. Stok saat ini: ' . number_format($availableStock, 2, ',', '.'))
                ->withInput();
        }

        // Validasi minimum stock
        $minimumStock = (float) $product->minimum_stock;
        $afterStock = $availableStock - $quantity;
        if ($minimumStock > 0 && $afterStock < $minimumStock) {
            // Warning, bukan error
            session()->flash('warning', 'Stok setelah transaksi akan kurang dari minimum (' . number_format($minimumStock, 2, ',', '.') . ').');
        }

        if (($meta['expired_only'] ?? false) === true) {
            $availableExpired = $this->inventoryService->getAvailableExpiredQty($productId);

            if ($quantity - $availableExpired > 0.0001) {
                return back()
                    ->with('error', 'Qty melebihi stok expired tersedia. Stok expired saat ini: ' . number_format($availableExpired, 2, ',', '.'))
                    ->withInput();
            }
        }

        try {
            $this->inventoryService->removeStockFefo($productId, $quantity, $movementCode, $meta);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable $exception) {
            return back()->with('error', 'Transaksi gagal: ' . $exception->getMessage())->withInput();
        }

        return back()->with('success', $successMessage);
    }
}

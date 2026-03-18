<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\SalesTransaction;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleRepository
{
    public function __construct(private SalesTransaction $model) {}

    /**
     * List sales transactions
     */
    public function index($perPage, $search, $startDate = null, $endDate = null, $tipe = null, $userId = null, $salesRepId = null)
    {
        $authUser = Auth::user();
        $isKasir = $authUser?->hasRole('kasir');

        $query = $this->model->newQuery()
            ->with(['user', 'salesRep', 'salesUser', 'tax', 'serviceOrder.technician', 'serviceOrder.parts.product.masterProduct'])
            ->withCount('items')
            ->withSum('items', 'qty')
            ->search($search)
            ->dateRange($startDate, $endDate)
            ->tipe($tipe)
            ->user($userId)
            ->when($isKasir, fn($q) => $q->where('user_id', $authUser->id))
            ->salesRep($salesRepId)
            ->latest('tanggal')
            ->latest('created_at');

        $data = $query->paginate($perPage);

        return ResponseHelper::success(
            new PaginateResource($data, SaleResource::class),
            'Sales retrieved successfully'
        );
    }

    /**
     * Get sales statistics for cards
     */
    public function stats($tipe = null)
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();

        $authUser = Auth::user();
        $isKasir = $authUser?->hasRole('kasir');

        $baseQuery = $this->model->newQuery()
            ->when($tipe, fn($q) => $q->where('tipe', $tipe))
            ->when($isKasir, fn($q) => $q->where('user_id', $authUser->id));

        $stats = [
            'today' => (float) (clone $baseQuery)->where('tanggal', '>=', $today)->sum('grand_total'),
            'month' => (float) (clone $baseQuery)->where('tanggal', '>=', $thisMonth)->sum('grand_total'),
            'year' => (float) (clone $baseQuery)->where('tanggal', '>=', $thisYear)->sum('grand_total'),
            'total' => (float) (clone $baseQuery)->sum('grand_total'),
        ];

        return ResponseHelper::success($stats, 'Sales statistics retrieved successfully');
    }

    /**
     * Show sale detail
     */
    public function show($id)
    {
        $authUser = Auth::user();
        $isKasir = $authUser?->hasRole('kasir');

        $sale = $this->model->newQuery()->with([
            'user',
            'salesRep',
            'salesUser',
            'tax',
            'serviceOrder.technician',
            'serviceOrder.parts.product.masterProduct',
            'items.product.masterProduct.brand',
            'items.product.unit',
            'items.product.grade',
        ])->when($isKasir, fn($q) => $q->where('user_id', $authUser->id))
            ->findOrFail($id);

        return ResponseHelper::success(new SaleResource($sale), 'Sale retrieved successfully');
    }

    /**
     * Process a new POS sales transaction
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $resolvedSalesRepId = $this->resolveSalesRepId($data['sales_rep_id'] ?? null);

            // Validate items availability first
            $items = $data['items'];
            foreach ($items as $item) {
                $product = Product::with('masterProduct')->lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    throw new Exception("Product ID {$item['product_id']} not found");
                }
                $availableStock = $this->getAvailableStockForSale($product);
                if ($availableStock < $item['qty']) {
                    throw new Exception("Stock insufficient for {$product->nama}. Available: {$availableStock}");
                }
            }

            // Create Header
            $sale = $this->model->create([
                'no_invoice' => $this->generateInvoiceNumber(),
                'tanggal' => $data['tanggal'] ?? date('Y-m-d'),
                'pelanggan' => $data['pelanggan'] ?? 'Umum',
                'user_id' => $data['user_id'] ?? Auth::id(),
                'sales_rep_id' => $resolvedSalesRepId,
                'subtotal' => 0, // Will be calculated
                'diskon_persen' => $data['diskon_persen'] ?? 0,
                'diskon_nominal' => $data['diskon_nominal'] ?? 0,
                'tax_id' => $data['tax_id'] ?? null,
                'tax_persen' => $data['tax_persen'] ?? 0,
                'tax_nominal' => 0, // Will be calculated
                'grand_total' => 0, // Will be calculated
                'metode_pembayaran' => $data['metode_pembayaran'] ?? 'cash',
                'jumlah_bayar' => $data['jumlah_bayar'] ?? 0,
                'kembalian' => 0, // Will be calculated
                'tipe' => $data['tipe'] ?? 'penjualan',
            ]);

            // Add Items and REDUCE stock
            foreach ($items as $item) {
                $product = Product::with('masterProduct')->lockForUpdate()->findOrFail($item['product_id']);
                $consumptions = $this->consumeStockFifo($product, (int) $item['qty']);
                $hppTotal = collect($consumptions)->sum('subtotal_hpp');

                /** @var SaleItem $saleItem */
                $saleItem = $sale->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['qty'] * $item['harga_satuan'],
                    'hpp_total' => $hppTotal,
                ]);

                foreach ($consumptions as $consumption) {
                    $saleItem->allocations()->create([
                        'product_id' => $consumption['product_id'],
                        'qty' => $consumption['qty'],
                        'harga_modal' => $consumption['harga_modal'],
                        'subtotal_hpp' => $consumption['subtotal_hpp'],
                    ]);
                }
            }

            // Calculate Totals
            $sale->calculateTotals();

            return ResponseHelper::success(new SaleResource($sale->fresh(['items.product'])), 'Transaction completed successfully', 201);
        });
    }

    /**
     * Generate next invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = date('ymd');

        $latest = $this->model->withTrashed()
            ->where('no_invoice', 'like', "{$prefix}{$date}%")
            ->whereRaw('LENGTH(no_invoice) <= 12')
            ->orderByDesc('no_invoice')
            ->value('no_invoice');

        if ($latest) {
            $lastNum = (int) substr($latest, 9);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        $invoice = sprintf('%s%s%03d', $prefix, $date, $nextNum);

        while ($this->model->withTrashed()->where('no_invoice', $invoice)->exists()) {
            $nextNum++;
            $invoice = sprintf('%s%s%03d', $prefix, $date, $nextNum);
        }

        return $invoice;
    }

    /**
     * Update an existing POS sales transaction
     */
    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $resolvedSalesRepId = $this->resolveSalesRepId($data['sales_rep_id'] ?? null);
            $sale = $this->model->with('items.allocations')->findOrFail($id);

            // 1. Temporary Restore Stock for previous items
            foreach ($sale->items as $item) {
                if ($item->allocations->isNotEmpty()) {
                    foreach ($item->allocations as $allocation) {
                        if ($allocation->product) {
                            $allocation->product->increment('stok', $allocation->qty);
                        }
                    }
                } elseif ($item->product) {
                    // Backward compatibility for legacy records (before allocations existed)
                    $item->product->increment('stok', $item->qty);
                }
            }

            // 2. Validate new items availability
            $items = $data['items'];
            foreach ($items as $item) {
                $product = Product::with('masterProduct')->lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    throw new Exception("Product ID {$item['product_id']} not found");
                }
                $availableStock = $this->getAvailableStockForSale($product);
                if ($availableStock < $item['qty']) {
                    throw new Exception("Stock insufficient for {$product->nama}. Available: {$availableStock}");
                }
            }

            // 3. Update Header
            $sale->update([
                'tanggal' => $data['tanggal'] ?? $sale->tanggal,
                'pelanggan' => $data['pelanggan'] ?? $sale->pelanggan,
                'user_id' => $data['user_id'] ?? $sale->user_id,
                'sales_rep_id' => $resolvedSalesRepId,
                'diskon_persen' => $data['diskon_persen'] ?? 0,
                'diskon_nominal' => $data['diskon_nominal'] ?? 0,
                'tax_id' => $data['tax_id'] ?? null,
                'tax_persen' => $data['tax_persen'] ?? 0,
                'metode_pembayaran' => $data['metode_pembayaran'] ?? 'cash',
                'jumlah_bayar' => $data['jumlah_bayar'] ?? 0,
                'tipe' => $data['tipe'] ?? 'penjualan',
            ]);

            // 4. Delete old items and Add new items
            $sale->items()->delete();
            foreach ($items as $item) {
                $product = Product::with('masterProduct')->lockForUpdate()->findOrFail($item['product_id']);
                $consumptions = $this->consumeStockFifo($product, (int) $item['qty']);
                $hppTotal = collect($consumptions)->sum('subtotal_hpp');

                /** @var SaleItem $saleItem */
                $saleItem = $sale->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['qty'] * $item['harga_satuan'],
                    'hpp_total' => $hppTotal,
                ]);

                foreach ($consumptions as $consumption) {
                    $saleItem->allocations()->create([
                        'product_id' => $consumption['product_id'],
                        'qty' => $consumption['qty'],
                        'harga_modal' => $consumption['harga_modal'],
                        'subtotal_hpp' => $consumption['subtotal_hpp'],
                    ]);
                }
            }

            // 5. Calculate Totals
            $sale->calculateTotals();

            return ResponseHelper::success(new SaleResource($sale->fresh(['items.product'])), 'Transaction updated successfully');
        });
    }

    /**
     * Delete a sale transaction and RESTORE stock
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $sale = $this->model->with('items.allocations.product', 'items.product')->findOrFail($id);

            // Restore stock
            foreach ($sale->items as $item) {
                if ($item->allocations->isNotEmpty()) {
                    foreach ($item->allocations as $allocation) {
                        if ($allocation->product) {
                            $allocation->product->increment('stok', $allocation->qty);
                        }
                    }
                } elseif ($item->product) {
                    // Backward compatibility for legacy records (before allocations existed)
                    $item->product->increment('stok', $item->qty);
                }
            }

            $sale->forceDelete();

            return ResponseHelper::success(null, 'Sale deleted and stock restored successfully');
        });
    }

    /**
     * Get available stock for selected product.
     * For non-serialized product, stock is summed by same SKU (master + grade + unit).
     */
    private function getAvailableStockForSale(Product $selectedProduct): int
    {
        if ($this->isSerializedProduct($selectedProduct)) {
            return (int) $selectedProduct->stok;
        }

        return (int) Product::query()
            ->where('master_product_id', $selectedProduct->master_product_id)
            ->where('grade_id', $selectedProduct->grade_id)
            ->where('unit_id', $selectedProduct->unit_id)
            ->sum('stok');
    }

    /**
     * Consume stock with FIFO allocation.
     * Serialized products consume from selected row only.
     */
    private function consumeStockFifo(Product $selectedProduct, int $qty): array
    {
        if ($this->isSerializedProduct($selectedProduct)) {
            if ($selectedProduct->stok < $qty) {
                throw new Exception("Stock insufficient for {$selectedProduct->nama}. Available: {$selectedProduct->stok}");
            }

            $selectedProduct->decrement('stok', $qty);

            return [[
                'product_id' => $selectedProduct->id,
                'qty' => $qty,
                'harga_modal' => (float) $selectedProduct->harga_modal,
                'subtotal_hpp' => (float) $selectedProduct->harga_modal * $qty,
            ]];
        }

        $lotsQuery = Product::query()
            ->where('master_product_id', $selectedProduct->master_product_id)
            ->where('unit_id', $selectedProduct->unit_id)
            ->where('stok', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate();

        if ($selectedProduct->grade_id) {
            $lotsQuery->where('grade_id', $selectedProduct->grade_id);
        } else {
            $lotsQuery->whereNull('grade_id');
        }

        $lots = $lotsQuery->get();
        $remaining = $qty;
        $allocations = [];

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $takeQty = min($remaining, (int) $lot->stok);
            if ($takeQty <= 0) {
                continue;
            }

            $lot->decrement('stok', $takeQty);
            $remaining -= $takeQty;

            $allocations[] = [
                'product_id' => $lot->id,
                'qty' => $takeQty,
                'harga_modal' => (float) $lot->harga_modal,
                'subtotal_hpp' => (float) $lot->harga_modal * $takeQty,
            ];
        }

        if ($remaining > 0) {
            throw new Exception("Stock insufficient for {$selectedProduct->nama}.");
        }

        return $allocations;
    }

    private function isSerializedProduct(Product $product): bool
    {
        return !empty($product->imei1) || !empty($product->imei2);
    }

    /**
     * Make sure sales_rep_id always points to sales_reps table (FK-safe).
     * If FE sends a user id, mirror it into sales_reps first.
     */
    private function resolveSalesRepId(?string $salesRepId): ?string
    {
        if (!$salesRepId) {
            return null;
        }

        if (SalesRep::query()->whereKey($salesRepId)->exists()) {
            return $salesRepId;
        }

        $user = User::query()
            ->whereKey($salesRepId)
            ->with('roles')
            ->first();

        if (!$user) {
            throw new Exception('Sales yang dipilih tidak valid.');
        }

        $isSuperAdmin = $user->roles->contains(fn($role) => $role->name === 'super-admin');
        if ($isSuperAdmin) {
            throw new Exception('User super-admin tidak bisa dipilih sebagai sales.');
        }

        $salesRep = SalesRep::query()->whereKey($user->id)->first();
        if (!$salesRep) {
            $salesRep = new SalesRep();
            $salesRep->id = $user->id;
        }
        $salesRep->nama = $user->name;
        $salesRep->save();

        return $user->id;
    }
}

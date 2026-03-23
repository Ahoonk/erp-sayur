<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryService
{
    /**
     * @var array<int, string>
     */
    private const OUT_MOVEMENT_CODES = ['out', 'reject', 'waste', 'expired'];

    public function addStock(int $productId, array $payload): ProductBatch
    {
        return DB::transaction(function () use ($productId, $payload) {
            $quantity = (float) ($payload['quantity'] ?? 0);

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Qty stok masuk harus lebih besar dari 0.');
            }

            $purchasePrice = (float) ($payload['purchase_price'] ?? 0);
            $receivedAt = $payload['received_at'] ?? now();

            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            $batchNumber = $this->generateBatchNumber($productId);

            $batch = ProductBatch::create([
                'product_id' => $productId,
                'batch_number' => $batchNumber,
                'expired_date' => $payload['expired_date'] ?? null,
                'purchase_price' => $purchasePrice,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'received_at' => $receivedAt,
            ]);

            StockMovement::create([
                'product_id' => $productId,
                'product_batch_id' => $batch->id,
                'movement_type' => 'in',
                'movement_code' => 'in',
                'reference_type' => $payload['reference_type'] ?? 'opening_balance',
                'reference_id' => $payload['reference_id'] ?? null,
                'qty_in' => $quantity,
                'qty_out' => 0,
                'unit_cost' => $purchasePrice,
                'notes' => $payload['notes'] ?? null,
                'moved_at' => $receivedAt,
            ]);

            $this->applyInCosting($product, $quantity, $purchasePrice);

            return $batch;
        });
    }

    public function removeStockFefo(int $productId, float $qty, string $movementCode = 'out', array $meta = []): void
    {
        if (!in_array($movementCode, self::OUT_MOVEMENT_CODES, true)) {
            throw new InvalidArgumentException('Movement code tidak valid.');
        }

        DB::transaction(function () use ($productId, $qty, $movementCode, $meta) {
            if ($qty <= 0) {
                throw new InvalidArgumentException('Qty stok keluar harus lebih besar dari 0.');
            }

            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            $issueUnitCost = max((float) $product->average_cost, 0.0);
            $remaining = (float) $qty;

            $batchesQuery = ProductBatch::query()
                ->where('product_id', $productId)
                ->where('remaining_quantity', '>', 0);

            if (($meta['expired_only'] ?? false) === true) {
                $batchesQuery
                    ->whereNotNull('expired_date')
                    ->whereDate('expired_date', '<=', now()->toDateString());
            }

            $batches = $batchesQuery
                ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expired_date')
                ->orderByRaw('CASE WHEN received_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('received_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min((float) $batch->remaining_quantity, $remaining);

                if ($take <= 0) {
                    continue;
                }

                $batch->remaining_quantity = (float) $batch->remaining_quantity - $take;
                $batch->save();

                StockMovement::create([
                    'product_id' => $productId,
                    'product_batch_id' => $batch->id,
                    'movement_type' => 'out',
                    'movement_code' => $movementCode,
                    'reference_type' => $meta['reference_type'] ?? $movementCode,
                    'reference_id' => $meta['reference_id'] ?? null,
                    'qty_in' => 0,
                    'qty_out' => $take,
                    'unit_cost' => $issueUnitCost,
                    'notes' => $meta['notes'] ?? null,
                    'moved_at' => $meta['moved_at'] ?? now(),
                ]);

                $remaining -= $take;
            }

            if ($remaining > 0) {
                if (($meta['expired_only'] ?? false) === true) {
                    throw new RuntimeException('Stok expired tidak mencukupi untuk transaksi ini.');
                }

                throw new RuntimeException('Stok tidak mencukupi untuk pengeluaran FEFO.');
            }

            $this->applyOutCosting($product, (float) $qty, $issueUnitCost);
        });
    }

    public function getAvailableExpiredQty(int $productId): float
    {
        return (float) ProductBatch::query()
            ->where('product_id', $productId)
            ->whereNotNull('expired_date')
            ->whereDate('expired_date', '<=', now()->toDateString())
            ->sum('remaining_quantity');
    }

    private function applyInCosting(Product $product, float $quantity, float $purchasePrice): void
    {
        $currentQty = (float) $product->stock_qty;
        $currentValue = (float) $product->stock_value;

        $newQty = $currentQty + $quantity;
        $newValue = $currentValue + ($quantity * $purchasePrice);
        $newAverage = $newQty > 0 ? ($newValue / $newQty) : 0;

        $product->stock_qty = round($newQty, 3);
        $product->stock_value = round(max($newValue, 0), 4);
        $product->average_cost = round(max($newAverage, 0), 4);
        $product->save();
    }

    private function applyOutCosting(Product $product, float $quantity, float $issueUnitCost): void
    {
        $currentQty = (float) $product->stock_qty;
        $currentValue = (float) $product->stock_value;

        if ($quantity - $currentQty > 0.0001) {
            throw new RuntimeException('Stok produk tidak konsisten. Qty agregat kurang dari transaksi keluar.');
        }

        $issuedValue = $quantity * $issueUnitCost;

        $newQty = max($currentQty - $quantity, 0);
        $newValue = max($currentValue - $issuedValue, 0);

        if ($newQty < 0.0001) {
            $newQty = 0;
            $newValue = 0;
        }

        $newAverage = $newQty > 0 ? ($newValue / $newQty) : 0;

        $product->stock_qty = round($newQty, 3);
        $product->stock_value = round($newValue, 4);
        $product->average_cost = round(max($newAverage, 0), 4);
        $product->save();
    }

    private function generateBatchNumber(int $productId): string
    {
        $activeNumbers = ProductBatch::query()
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->lockForUpdate()
            ->pluck('batch_number');

        $activeNumeric = $activeNumbers
            ->filter(fn ($value) => ctype_digit((string) $value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $candidate = 1;
        while ($activeNumeric->contains($candidate)) {
            $candidate++;
        }

        return (string) $candidate;
    }
}

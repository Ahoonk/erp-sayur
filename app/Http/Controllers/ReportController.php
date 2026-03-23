<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function stockAging(Request $request): View
    {
        $productId = $request->integer('product_id');

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $batches = ProductBatch::query()
            ->with(['product.unit'])
            ->where('remaining_quantity', '>', 0)
            ->when($productId > 0, fn (Builder $query) => $query->where('product_id', $productId))
            ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expired_date')
            ->orderByRaw('CASE WHEN received_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();

        $today = now()->startOfDay();

        $rows = $batches->map(function (ProductBatch $batch) use ($today) {
            $receivedAt = $batch->received_at?->copy()->startOfDay();
            $expiredAt = $batch->expired_date?->copy()->startOfDay();

            $ageDays = $receivedAt ? $receivedAt->diffInDays($today) : null;
            $daysToExpire = $expiredAt ? $today->diffInDays($expiredAt, false) : null;

            $remainingQty = (float) $batch->remaining_quantity;
            $unitCost = (float) $batch->purchase_price;

            return [
                'product_name' => $batch->product?->name,
                'sku' => $batch->product?->sku,
                'unit' => $batch->product?->unit?->symbol,
                'batch_number' => $batch->batch_number,
                'received_at' => $batch->received_at,
                'expired_date' => $batch->expired_date,
                'age_days' => $ageDays,
                'days_to_expire' => $daysToExpire,
                'aging_bucket' => $this->resolveAgingBucket($ageDays),
                'remaining_qty' => $remainingQty,
                'remaining_value' => $remainingQty * $unitCost,
            ];
        });

        $summary = $rows
            ->groupBy('aging_bucket')
            ->map(function (Collection $items) {
                return [
                    'qty' => $items->sum('remaining_qty'),
                    'value' => $items->sum('remaining_value'),
                ];
            })
            ->sortKeys();

        return view('reports.stock-aging', [
            'products' => $products,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => [
                'product_id' => $productId > 0 ? $productId : null,
            ],
        ]);
    }

    public function realMargin(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $aggregates = $this->applyMovementDateFilters(StockMovement::query(), $dateFrom, $dateTo)
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN movement_code = 'in' THEN qty_in ELSE 0 END) AS purchase_qty")
            ->selectRaw("SUM(CASE WHEN movement_code = 'in' THEN qty_in * unit_cost ELSE 0 END) AS purchase_cost")
            ->selectRaw("SUM(CASE WHEN movement_code = 'out' THEN qty_out ELSE 0 END) AS sold_qty")
            ->selectRaw("SUM(CASE WHEN movement_code IN ('waste', 'expired') THEN qty_out ELSE 0 END) AS waste_qty")
            ->selectRaw("SUM(CASE WHEN movement_code IN ('waste', 'expired') THEN qty_out * unit_cost ELSE 0 END) AS waste_cost")
            ->selectRaw("SUM(CASE WHEN movement_code = 'reject' THEN qty_out ELSE 0 END) AS reject_qty")
            ->selectRaw("SUM(CASE WHEN movement_code = 'reject' THEN qty_out * unit_cost ELSE 0 END) AS reject_cost")
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $rows = Product::query()
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($aggregates) {
                $agg = $aggregates->get($product->id);

                $purchaseCost = (float) ($agg?->purchase_cost ?? 0);
                $soldQty = (float) ($agg?->sold_qty ?? 0);
                $wasteCost = (float) ($agg?->waste_cost ?? 0);
                $realHpp = $soldQty > 0 ? (($purchaseCost + $wasteCost) / $soldQty) : 0;
                $revenue = $soldQty * (float) $product->selling_price;
                $cogs = $realHpp * $soldQty;
                $marginValue = $revenue - $cogs;
                $marginPercent = $revenue > 0 ? (($marginValue / $revenue) * 100) : 0;

                return [
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => (float) $product->selling_price,
                    'purchase_qty' => (float) ($agg?->purchase_qty ?? 0),
                    'purchase_cost' => $purchaseCost,
                    'sold_qty' => $soldQty,
                    'waste_qty' => (float) ($agg?->waste_qty ?? 0),
                    'waste_cost' => $wasteCost,
                    'reject_qty' => (float) ($agg?->reject_qty ?? 0),
                    'reject_cost' => (float) ($agg?->reject_cost ?? 0),
                    'real_hpp' => $realHpp,
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'margin_value' => $marginValue,
                    'margin_percent' => $marginPercent,
                ];
            })
            ->filter(function (array $row) {
                return $row['purchase_qty'] > 0 || $row['sold_qty'] > 0 || $row['waste_qty'] > 0 || $row['reject_qty'] > 0;
            })
            ->values();

        $summary = [
            'purchase_cost' => $rows->sum('purchase_cost'),
            'revenue' => $rows->sum('revenue'),
            'waste_cost' => $rows->sum('waste_cost'),
            'reject_cost' => $rows->sum('reject_cost'),
            'margin_value' => $rows->sum('margin_value'),
        ];

        return view('reports.real-margin', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function waste(Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $aggregates = $this->applyMovementDateFilters(StockMovement::query(), $dateFrom, $dateTo)
            ->whereIn('movement_code', ['waste', 'expired', 'reject'])
            ->select('product_id', 'movement_code')
            ->selectRaw('SUM(qty_out) AS total_qty')
            ->selectRaw('SUM(qty_out * unit_cost) AS total_cost')
            ->groupBy('product_id', 'movement_code')
            ->orderBy('movement_code')
            ->get();

        $products = Product::query()
            ->whereIn('id', $aggregates->pluck('product_id')->unique())
            ->get(['id', 'name', 'sku'])
            ->keyBy('id');

        $rows = $aggregates->map(function (StockMovement $aggregate) use ($products) {
            $product = $products->get($aggregate->product_id);

            return [
                'product_name' => $product?->name,
                'sku' => $product?->sku,
                'movement_code' => $aggregate->movement_code,
                'total_qty' => (float) ($aggregate->total_qty ?? 0),
                'total_cost' => (float) ($aggregate->total_cost ?? 0),
            ];
        });

        $summary = [
            'waste_qty' => $rows->where('movement_code', 'waste')->sum('total_qty'),
            'waste_cost' => $rows->where('movement_code', 'waste')->sum('total_cost'),
            'expired_qty' => $rows->where('movement_code', 'expired')->sum('total_qty'),
            'expired_cost' => $rows->where('movement_code', 'expired')->sum('total_cost'),
            'reject_qty' => $rows->where('movement_code', 'reject')->sum('total_qty'),
            'reject_cost' => $rows->where('movement_code', 'reject')->sum('total_cost'),
        ];

        return view('reports.waste', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function ledger(Request $request): View
    {
        $productId = $request->integer('product_id');
        $movementCode = trim((string) $request->input('movement_code', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $query = StockMovement::query()
            ->with(['product.unit', 'batch'])
            ->when($productId > 0, fn (Builder $builder) => $builder->where('product_id', $productId))
            ->when($movementCode !== '', fn (Builder $builder) => $builder->where('movement_code', $movementCode));

        $this->applyMovementDateFilters($query, $dateFrom, $dateTo);

        $movements = $query
            ->orderBy('moved_at')
            ->orderBy('id')
            ->limit(2000)
            ->get();

        $openingBalances = collect();

        if (!empty($dateFrom)) {
            $openingBalances = StockMovement::query()
                ->select('product_id')
                ->selectRaw('SUM(qty_in - qty_out) AS opening_balance')
                ->when($productId > 0, fn (Builder $builder) => $builder->where('product_id', $productId))
                ->when($movementCode !== '', fn (Builder $builder) => $builder->where('movement_code', $movementCode))
                ->where('moved_at', '<', $dateFrom . ' 00:00:00')
                ->groupBy('product_id')
                ->pluck('opening_balance', 'product_id');
        }

        $running = [];
        foreach ($openingBalances as $pid => $openingBalance) {
            $running[(int) $pid] = (float) $openingBalance;
        }

        $rows = $movements->map(function (StockMovement $movement) use (&$running) {
            $pid = (int) $movement->product_id;
            $running[$pid] = ($running[$pid] ?? 0) + (float) $movement->qty_in - (float) $movement->qty_out;

            $qty = (float) ($movement->qty_in > 0 ? $movement->qty_in : $movement->qty_out);

            return [
                'moved_at' => $movement->moved_at,
                'product_name' => $movement->product?->name,
                'sku' => $movement->product?->sku,
                'unit' => $movement->product?->unit?->symbol,
                'batch_number' => $movement->batch?->batch_number,
                'movement_code' => $movement->movement_code,
                'reference_type' => $movement->reference_type,
                'qty_in' => (float) $movement->qty_in,
                'qty_out' => (float) $movement->qty_out,
                'unit_cost' => (float) $movement->unit_cost,
                'movement_value' => $qty * (float) $movement->unit_cost,
                'running_balance' => $running[$pid],
                'notes' => $movement->notes,
            ];
        });

        return view('reports.ledger', [
            'products' => $products,
            'rows' => $rows,
            'filters' => [
                'product_id' => $productId > 0 ? $productId : null,
                'movement_code' => $movementCode !== '' ? $movementCode : null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'is_limited' => $rows->count() >= 2000,
        ]);
    }

    private function applyMovementDateFilters(Builder $query, ?string $dateFrom, ?string $dateTo): Builder
    {
        return $query
            ->when(!empty($dateFrom), fn (Builder $builder) => $builder->where('moved_at', '>=', $dateFrom . ' 00:00:00'))
            ->when(!empty($dateTo), fn (Builder $builder) => $builder->where('moved_at', '<=', $dateTo . ' 23:59:59'));
    }

    private function resolveAgingBucket(?int $ageDays): string
    {
        if ($ageDays === null) {
            return 'Unknown';
        }

        return match (true) {
            $ageDays <= 30 => '0-30 hari',
            $ageDays <= 60 => '31-60 hari',
            $ageDays <= 90 => '61-90 hari',
            default => '> 90 hari',
        };
    }
}

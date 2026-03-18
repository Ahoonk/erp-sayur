<?php
namespace App\Repositories;
use App\Helpers\ResponseHelper;
use App\Models\KatalogBarang;
use App\Models\PurchaseItem;
use App\Models\StockBatch;
use App\Services\ExpiredStockService;
use Illuminate\Support\Facades\DB;

class StockRepository {
    public function __construct(private ExpiredStockService $expiredStockService) {}

    /**
     * Stock summary: list all katalog barang with total stock and avg modal
     */
    public function summary($perPage, $search, $categoryId = null) {
        $this->expiredStockService->applyExpiredBatches();

        $query = KatalogBarang::query()
            ->with(['category', 'unit'])
            ->search($search)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->orderBy('kode_barang');

        // Add stock aggregates from stock_batches
        $query->withSum(['stockBatches as total_stok' => fn($q) => $q->where('qty_sisa', '>', 0)], 'qty_sisa');

        $data = $query->paginate($perPage);

        // Compute avg modal for each item
        $ids = $data->pluck('id');
        $avgModals = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->where('qty_sisa', '>', 0)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('SUM(qty_sisa * harga_beli) / SUM(qty_sisa) as modal_rata_rata'))
            ->pluck('modal_rata_rata', 'katalog_barang_id');

        $items = $data->getCollection()->map(function ($item) use ($avgModals) {
            return [
                'id' => $item->id,
                'kode_barang' => $item->kode_barang,
                'nama_barang' => $item->nama_barang,
                'category' => $item->category ? ['id' => $item->category->id, 'nama' => $item->category->nama] : null,
                'unit' => $item->unit ? ['id' => $item->unit->id, 'nama' => $item->unit->nama] : null,
                'total_stok' => (float) ($item->total_stok ?? 0),
                'modal_rata_rata' => round((float) ($avgModals[$item->id] ?? 0), 2),
            ];
        });

        return ResponseHelper::success([
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ], 'Stock summary retrieved successfully');
    }

    /**
     * Expiring stock list (by batch)
     */
    public function expiring($perPage, $search, $startDate = null, $endDate = null)
    {
        $this->expiredStockService->applyExpiredBatches();

        $query = StockBatch::query()
            ->with(['katalogBarang.category', 'katalogBarang.unit'])
            ->whereNotNull('expired_at')
            ->where('qty_sisa', '>', 0)
            ->when($startDate, fn($q) => $q->whereDate('expired_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expired_at', '<=', $endDate))
            ->when($search, fn($q) => $q->whereHas('katalogBarang', function ($sq) use ($search) {
                $sq->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            }))
            ->orderBy('expired_at')
            ->orderBy('created_at');

        $data = $query->paginate($perPage);
        $today = now()->startOfDay();

        $items = $data->getCollection()->map(fn($batch) => [
            'id' => $batch->id,
            'expired_at' => $batch->expired_at?->format('Y-m-d'),
            'days_left' => $batch->expired_at ? $today->diffInDays($batch->expired_at, false) : null,
            'qty_sisa' => (float) $batch->qty_sisa,
            'katalog_barang' => [
                'id' => $batch->katalogBarang?->id,
                'kode_barang' => $batch->katalogBarang?->kode_barang,
                'nama_barang' => $batch->katalogBarang?->nama_barang,
                'unit' => $batch->katalogBarang?->unit?->nama,
                'category' => $batch->katalogBarang?->category?->nama,
            ],
        ]);

        return ResponseHelper::success([
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ], 'Expiring stock retrieved successfully');
    }

    /**
     * Mutasi: list all purchase items as mutation records (sorted newest first)
     */
    public function mutasi($perPage, $search, $startDate = null, $endDate = null, $categoryId = null, $katalogBarangId = null) {
        $query = PurchaseItem::query()
            ->with([
                'purchase.supplier',
                'katalogBarang.category',
                'katalogBarang.unit',
                'stockBatch',
            ])
            ->whereHas('purchase', function ($q) use ($startDate, $endDate) {
                $q->dateRange($startDate, $endDate);
            })
            ->when($katalogBarangId, fn($q) => $q->where('katalog_barang_id', $katalogBarangId))
            ->when($categoryId, fn($q) => $q->whereHas('katalogBarang', fn($sq) => $sq->where('category_id', $categoryId)))
            ->when($search, fn($q) => $q->where(function ($sq) use ($search) {
                $sq->whereHas('katalogBarang', fn($kq) => $kq->where('nama_barang', 'like', "%{$search}%")->orWhere('kode_barang', 'like', "%{$search}%"))
                    ->orWhereHas('purchase', fn($pq) => $pq->where('no_invoice', 'like', "%{$search}%"));
            }))
            ->latest();

        $data = $query->paginate($perPage);

        $items = $data->getCollection()->map(fn($item) => [
            'id' => $item->id,
            'purchase_id' => $item->purchase?->id,
            'no_invoice' => $item->purchase?->no_invoice,
            'tanggal' => $item->purchase?->tanggal?->format('Y-m-d'),
            'supplier' => $item->purchase?->supplier?->nama,
            'kode_barang' => $item->katalogBarang?->kode_barang,
            'nama_barang' => $item->katalogBarang?->nama_barang,
            'category' => $item->katalogBarang?->category?->nama,
            'unit' => $item->katalogBarang?->unit?->nama,
            'qty' => (float) $item->qty,
            'harga_beli' => (float) $item->harga_beli,
            'subtotal' => (float) $item->subtotal,
            'qty_sisa' => (float) ($item->stockBatch?->qty_sisa ?? 0),
        ]);

        return ResponseHelper::success([
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ], 'Mutasi retrieved successfully');
    }
}

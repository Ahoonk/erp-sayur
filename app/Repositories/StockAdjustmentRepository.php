<?php
namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Models\KatalogBarang;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Services\ExpiredStockService;
use Illuminate\Support\Facades\DB;

class StockAdjustmentRepository
{
    public function __construct(private ExpiredStockService $expiredStockService) {}

    public function index($perPage, $search = '', $reason = null, $startDate = null, $endDate = null)
    {
        $this->expiredStockService->applyExpiredBatches();

        $query = StockAdjustment::query()
            ->with(['katalogBarang.category', 'katalogBarang.unit', 'creator'])
            ->when($reason, fn($q) => $q->where('reason', $reason))
            ->when($search, fn($q) => $q->whereHas('katalogBarang', function ($sq) use ($search) {
                $sq->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            }))
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->latest();

        $data = $query->paginate($perPage);

        $items = $data->getCollection()->map(fn($item) => [
            'id' => $item->id,
            'tanggal' => $item->tanggal?->format('Y-m-d'),
            'katalog_barang' => [
                'id' => $item->katalogBarang?->id,
                'kode_barang' => $item->katalogBarang?->kode_barang,
                'nama_barang' => $item->katalogBarang?->nama_barang,
                'unit' => $item->katalogBarang?->unit?->nama,
                'category' => $item->katalogBarang?->category?->nama,
            ],
            'reason' => $item->reason,
            'qty' => (float) $item->qty,
            'keterangan' => $item->keterangan,
            'created_by' => $item->creator?->name,
        ]);

        return ResponseHelper::success([
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ], 'Stock adjustments retrieved successfully');
    }

    public function store(array $data, ?string $userId = null)
    {
        $this->expiredStockService->applyExpiredBatches();

        return DB::transaction(function () use ($data, $userId) {
            $katalogId = $data['katalog_barang_id'];
            $qty = (float) $data['qty'];

            $katalog = KatalogBarang::findOrFail($katalogId);

            $available = (float) StockBatch::query()
                ->where('katalog_barang_id', $katalogId)
                ->where('qty_sisa', '>', 0)
                ->sum('qty_sisa');

            if ($available <= 0) {
                return ResponseHelper::error('Stok kosong, tidak dapat melakukan penyusutan.', 422);
            }

            if ($qty > $available) {
                return ResponseHelper::error('Stok tidak mencukupi. Stok tersedia: ' . $available, 422);
            }

            $remaining = $qty;
            $batches = StockBatch::query()
                ->where('katalog_barang_id', $katalogId)
                ->where('qty_sisa', '>', 0)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
                $take = min((float) $batch->qty_sisa, $remaining);
                $batch->qty_sisa = round(((float) $batch->qty_sisa) - $take, 3);
                $batch->save();
                $remaining = round($remaining - $take, 3);
            }

            $adjustment = StockAdjustment::create([
                'katalog_barang_id' => $katalogId,
                'tanggal' => $data['tanggal'],
                'reason' => $data['reason'],
                'qty' => $qty,
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => $userId,
            ]);

            $adjustment->load(['katalogBarang.unit', 'katalogBarang.category', 'creator']);

            return ResponseHelper::success([
                'id' => $adjustment->id,
                'katalog_barang' => [
                    'id' => $katalog->id,
                    'kode_barang' => $katalog->kode_barang,
                    'nama_barang' => $katalog->nama_barang,
                ],
                'tanggal' => $adjustment->tanggal?->format('Y-m-d'),
                'reason' => $adjustment->reason,
                'qty' => (float) $adjustment->qty,
                'keterangan' => $adjustment->keterangan,
            ], 'Penyusutan stok berhasil disimpan');
        });
    }
}

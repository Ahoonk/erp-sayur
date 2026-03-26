<?php
namespace App\Repositories;
use App\Helpers\ResponseHelper;
use App\Models\KatalogBarang;
use App\Models\PricelistMitra;
use App\Models\PricelistMitraItem;
use App\Models\PricelistUmum;
use App\Models\PricelistUmumItem;
use App\Models\StockBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PricelistRepository {

    // ========================
    // Pricelist Umum
    // ========================

    public function indexUmum($perPage, $search) {
        $data = PricelistUmum::query()
            ->with('user')
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('periode')
            ->paginate($perPage);

        return ResponseHelper::success([
            'data' => $data->getCollection()->map(fn($p) => [
                'id' => $p->id,
                'tahun' => $p->tahun,
                'bulan' => $p->bulan,
                'periode' => $p->periode,
                'periode_label' => $p->periode_label,
                'items_count' => $p->items()->count(),
                'created_by' => $p->user?->name,
                'created_at' => $p->created_at,
            ]),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ], 'Pricelist umum retrieved');
    }

    public function showUmum($id) {
        $pricelist = PricelistUmum::with(['items.katalogBarang.category', 'items.katalogBarang.unit'])->findOrFail($id);
        return ResponseHelper::success([
            'id' => $pricelist->id,
            'tahun' => $pricelist->tahun,
            'bulan' => $pricelist->bulan,
            'periode' => $pricelist->periode,
            'periode_label' => $pricelist->periode_label,
            'items' => $pricelist->items->map(fn($item) => [
                'id' => $item->id,
                'katalog_barang_id' => $item->katalog_barang_id,
                'kode_barang' => $item->katalogBarang?->kode_barang,
                'nama_barang' => $item->katalogBarang?->nama_barang,
                'category' => $item->katalogBarang?->category?->nama,
                'unit' => $item->katalogBarang?->unit?->nama,
                'modal_rata_rata' => (float) $item->modal_rata_rata,
                'persentase' => $item->persentase !== null ? (float) $item->persentase : null,
                'harga_jual' => (float) $item->harga_jual,
            ]),
        ], 'Pricelist umum retrieved');
    }

    /**
     * Open/get pricelist umum for a specific period (create if not exists)
     * Returns all katalog barang with current avg modal and existing prices if any
     */
    public function getOrCreateUmum(int $tahun, int $bulan, int $periode) {
        $pricelist = PricelistUmum::firstOrCreate(
            ['tahun' => $tahun, 'bulan' => $bulan, 'periode' => $periode],
            ['user_id' => Auth::id()]
        );

        // Get all katalog barang
        $katalogList = KatalogBarang::with(['category', 'unit'])
            ->orderBy('kode_barang')
            ->get();

        // Compute avg modal for each
        $ids = $katalogList->pluck('id');
        $avgModals = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->where('qty_sisa', '>', 0)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('SUM(qty_sisa * harga_beli) / SUM(qty_sisa) as modal_rata_rata'))
            ->pluck('modal_rata_rata', 'katalog_barang_id');
        $stockTotals = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->where('qty_sisa', '>', 0)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('SUM(qty_sisa) as total_stok'))
            ->pluck('total_stok', 'katalog_barang_id');
        $lastPurchaseAt = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('MAX(created_at) as last_purchase_at'))
            ->pluck('last_purchase_at', 'katalog_barang_id');

        // Existing items
        $existingItems = $pricelist->items->keyBy('katalog_barang_id');

        $items = $katalogList->map(function ($k) use ($avgModals, $existingItems, $stockTotals, $lastPurchaseAt) {
            $existing = $existingItems->get($k->id);
            return [
                'katalog_barang_id' => $k->id,
                'kode_barang' => $k->kode_barang,
                'nama_barang' => $k->nama_barang,
                'category_id' => $k->category_id,
                'category' => $k->category?->nama,
                'unit' => $k->unit?->nama,
                'modal_rata_rata' => round((float) ($avgModals->get($k->id) ?? 0), 2),
                'stok' => (float) ($stockTotals->get($k->id) ?? 0),
                'last_purchase_at' => $lastPurchaseAt->get($k->id),
                'persentase' => $existing?->persentase !== null ? (float) $existing->persentase : null,
                'harga_jual' => $existing ? (float) $existing->harga_jual : null,
                'item_id' => $existing?->id,
            ];
        });

        return ResponseHelper::success([
            'pricelist_id' => $pricelist->id,
            'tahun' => $pricelist->tahun,
            'bulan' => $pricelist->bulan,
            'periode' => $pricelist->periode,
            'periode_label' => $pricelist->periode_label,
            'items' => $items,
        ], 'Pricelist umum ready');
    }

    /**
     * Save/update multiple pricelist items at once (bulk save)
     */
    public function saveUmumItems($pricelistId, array $items) {
        return DB::transaction(function () use ($pricelistId, $items) {
            $pricelist = PricelistUmum::findOrFail($pricelistId);

            foreach ($items as $itemData) {
                PricelistUmumItem::updateOrCreate(
                    ['pricelist_umum_id' => $pricelist->id, 'katalog_barang_id' => $itemData['katalog_barang_id']],
                    [
                        'modal_rata_rata' => $itemData['modal_rata_rata'] ?? 0,
                        'persentase' => $itemData['persentase'] ?? null,
                        'harga_jual' => $itemData['harga_jual'],
                    ]
                );
            }

            return ResponseHelper::success(null, 'Pricelist umum items saved successfully');
        });
    }

    public function destroyUmum($id) {
        PricelistUmum::findOrFail($id)->delete();
        return ResponseHelper::success(null, 'Pricelist umum deleted');
    }

    // ========================
    // Pricelist Mitra
    // ========================

    public function indexMitra($perPage, $search, $mitraId = null) {
        $data = PricelistMitra::query()
            ->with(['mitra', 'user'])
            ->when($mitraId, fn($q) => $q->where('mitra_id', $mitraId))
            ->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('periode')
            ->paginate($perPage);

        return ResponseHelper::success([
            'data' => $data->getCollection()->map(fn($p) => [
                'id' => $p->id,
                'mitra_id' => $p->mitra_id,
                'mitra' => $p->mitra?->nama,
                'tahun' => $p->tahun,
                'bulan' => $p->bulan,
                'periode' => $p->periode,
                'periode_label' => $p->periode_label,
                'items_count' => $p->items()->count(),
                'created_by' => $p->user?->name,
                'created_at' => $p->created_at,
            ]),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ], 'Pricelist mitra retrieved');
    }

    public function getOrCreateMitra(string $mitraId, int $tahun, int $bulan, int $periode) {
        $pricelist = PricelistMitra::firstOrCreate(
            ['mitra_id' => $mitraId, 'tahun' => $tahun, 'bulan' => $bulan, 'periode' => $periode],
            ['user_id' => Auth::id()]
        );

        $pricelist->load('mitra');

        // Get avg modals
        $katalogList = KatalogBarang::with(['category', 'unit'])->orderBy('kode_barang')->get();
        $ids = $katalogList->pluck('id');
        $avgModals = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->where('qty_sisa', '>', 0)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('SUM(qty_sisa * harga_beli) / SUM(qty_sisa) as modal_rata_rata'))
            ->pluck('modal_rata_rata', 'katalog_barang_id');
        $stockTotals = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->where('qty_sisa', '>', 0)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('SUM(qty_sisa) as total_stok'))
            ->pluck('total_stok', 'katalog_barang_id');
        $lastPurchaseAt = StockBatch::query()
            ->whereIn('katalog_barang_id', $ids)
            ->groupBy('katalog_barang_id')
            ->select('katalog_barang_id', DB::raw('MAX(created_at) as last_purchase_at'))
            ->pluck('last_purchase_at', 'katalog_barang_id');

        $existingItems = $pricelist->items->keyBy('katalog_barang_id');

        $items = $katalogList->map(function ($k) use ($avgModals, $existingItems, $stockTotals, $lastPurchaseAt) {
            $existing = $existingItems->get($k->id);
            return [
                'katalog_barang_id' => $k->id,
                'kode_barang' => $k->kode_barang,
                'nama_barang' => $k->nama_barang,
                'category_id' => $k->category_id,
                'category' => $k->category?->nama,
                'unit' => $k->unit?->nama,
                'modal_rata_rata' => round((float) ($avgModals->get($k->id) ?? 0), 2),
                'stok' => (float) ($stockTotals->get($k->id) ?? 0),
                'last_purchase_at' => $lastPurchaseAt->get($k->id),
                'persentase' => $existing?->persentase !== null ? (float) $existing->persentase : null,
                'harga_jual' => $existing ? (float) $existing->harga_jual : null,
                'item_id' => $existing?->id,
                'selected' => $existingItems->has($k->id),
            ];
        });

        return ResponseHelper::success([
            'pricelist_id' => $pricelist->id,
            'mitra_id' => $pricelist->mitra_id,
            'mitra' => $pricelist->mitra?->nama,
            'tahun' => $pricelist->tahun,
            'bulan' => $pricelist->bulan,
            'periode' => $pricelist->periode,
            'periode_label' => $pricelist->periode_label,
            'items' => $items,
        ], 'Pricelist mitra ready');
    }

    public function saveMitraItems($pricelistId, array $items) {
        return DB::transaction(function () use ($pricelistId, $items) {
            $pricelist = PricelistMitra::findOrFail($pricelistId);

            // Delete items not in the new list
            $katalogIds = collect($items)->pluck('katalog_barang_id');
            $pricelist->items()->whereNotIn('katalog_barang_id', $katalogIds)->delete();

            foreach ($items as $itemData) {
                PricelistMitraItem::updateOrCreate(
                    ['pricelist_mitra_id' => $pricelist->id, 'katalog_barang_id' => $itemData['katalog_barang_id']],
                    [
                        'modal_rata_rata' => $itemData['modal_rata_rata'] ?? 0,
                        'persentase' => $itemData['persentase'] ?? null,
                        'harga_jual' => $itemData['harga_jual'],
                    ]
                );
            }

            return ResponseHelper::success(null, 'Pricelist mitra items saved successfully');
        });
    }

    public function destroyMitra($id) {
        PricelistMitra::findOrFail($id)->delete();
        return ResponseHelper::success(null, 'Pricelist mitra deleted');
    }
}

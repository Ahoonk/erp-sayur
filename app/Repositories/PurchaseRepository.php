<?php
namespace App\Repositories;
use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use App\Http\Resources\PurchaseResource;
use App\Models\KatalogBarang;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRepository {
    public function __construct(private Purchase $model) {}

    public function index($perPage, $search, $startDate = null, $endDate = null, $supplierId = null) {
        $query = $this->model->newQuery()
            ->with(['supplier', 'user'])
            ->withCount('items')
            ->search($search)
            ->dateRange($startDate, $endDate)
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->latest('tanggal')
            ->latest('created_at');

        $data = $query->paginate($perPage);
        return ResponseHelper::success(new PaginateResource($data, PurchaseResource::class), 'Purchases retrieved successfully');
    }

    public function show($id) {
        $purchase = $this->model->with([
            'supplier', 'user',
            'items.katalogBarang.category',
            'items.katalogBarang.unit',
            'items.stockBatch',
        ])->findOrFail($id);
        return ResponseHelper::success(new PurchaseResource($purchase), 'Purchase retrieved successfully');
    }

    public function store(array $data) {
        $purchase = $this->model->create([
            'no_invoice' => $data['no_invoice'],
            'tanggal' => $data['tanggal'],
            'supplier_id' => $data['supplier_id'],
            'user_id' => Auth::id(),
            'keterangan' => $data['keterangan'] ?? null,
            'total' => 0,
        ]);
        $purchase->load(['supplier', 'user']);
        return ResponseHelper::success(new PurchaseResource($purchase), 'Purchase created successfully', 201);
    }

    public function addItem($purchaseId, array $data) {
        return DB::transaction(function () use ($purchaseId, $data) {
            $purchase = $this->model->findOrFail($purchaseId);

            $qty = (float) $data['qty'];
            $hargaBeli = (float) $data['harga_beli'];
            $subtotal = $qty * $hargaBeli;
            $expiredAt = $data['expired_at'] ?? null;

            $katalog = KatalogBarang::findOrFail($data['katalog_barang_id']);
            $kode = strtoupper($katalog->kode_barang ?? '');
            $isDryGood = str_starts_with($kode, 'D');

            if ($isDryGood && empty($expiredAt)) {
                return ResponseHelper::error('Tanggal expired wajib diisi untuk barang Dry Good (kode D).', 422);
            }

            if (!$isDryGood) {
                $expiredAt = null;
            }

            $item = PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'katalog_barang_id' => $data['katalog_barang_id'],
                'qty' => $qty,
                'harga_beli' => $hargaBeli,
                'subtotal' => $subtotal,
            ]);

            // Create stock batch (FIFO)
            StockBatch::create([
                'katalog_barang_id' => $data['katalog_barang_id'],
                'purchase_item_id' => $item->id,
                'qty_awal' => $qty,
                'qty_sisa' => $qty,
                'harga_beli' => $hargaBeli,
                'expired_at' => $expiredAt,
            ]);

            $purchase->recalculateTotal();
            $item->load(['katalogBarang.category', 'katalogBarang.unit', 'stockBatch']);

            return ResponseHelper::success([
                'item' => [
                    'id' => $item->id,
                    'katalog_barang_id' => $item->katalog_barang_id,
                    'kode_barang' => $item->katalogBarang?->kode_barang,
                    'nama_barang' => $item->katalogBarang?->nama_barang,
                    'unit' => $item->katalogBarang?->unit?->nama,
                    'category' => $item->katalogBarang?->category?->nama,
                    'qty' => (float) $item->qty,
                    'harga_beli' => (float) $item->harga_beli,
                    'subtotal' => (float) $item->subtotal,
                    'qty_sisa' => (float) ($item->stockBatch?->qty_sisa ?? $qty),
                ],
                'purchase_total' => (float) $purchase->fresh()->total,
            ], 'Item added successfully', 201);
        });
    }

    public function updateItem($purchaseId, $itemId, array $data) {
        return DB::transaction(function () use ($purchaseId, $itemId, $data) {
            $purchase = $this->model->findOrFail($purchaseId);
            $item = PurchaseItem::where('purchase_id', $purchaseId)->findOrFail($itemId);
            $batch = $item->stockBatch;

            $qty = isset($data['qty']) ? (float) $data['qty'] : (float) $item->qty;
            $hargaBeli = isset($data['harga_beli']) ? (float) $data['harga_beli'] : (float) $item->harga_beli;
            $subtotal = $qty * $hargaBeli;

            $item->update(['qty' => $qty, 'harga_beli' => $hargaBeli, 'subtotal' => $subtotal]);

            // Update stock batch
            if ($batch) {
                $qtyUsed = $batch->qty_awal - $batch->qty_sisa;
                $newSisa = max(0, $qty - $qtyUsed);
                $batch->update([
                    'qty_awal' => $qty,
                    'qty_sisa' => $newSisa,
                    'harga_beli' => $hargaBeli,
                ]);
            }

            $purchase->recalculateTotal();
            $item->load(['katalogBarang.category', 'katalogBarang.unit', 'stockBatch']);

            return ResponseHelper::success([
                'item' => [
                    'id' => $item->id,
                    'katalog_barang_id' => $item->katalog_barang_id,
                    'kode_barang' => $item->katalogBarang?->kode_barang,
                    'nama_barang' => $item->katalogBarang?->nama_barang,
                    'unit' => $item->katalogBarang?->unit?->nama,
                    'category' => $item->katalogBarang?->category?->nama,
                    'qty' => (float) $item->qty,
                    'harga_beli' => (float) $item->harga_beli,
                    'subtotal' => (float) $item->subtotal,
                    'qty_sisa' => (float) ($item->stockBatch?->qty_sisa ?? 0),
                ],
                'purchase_total' => (float) $purchase->fresh()->total,
            ], 'Item updated successfully');
        });
    }

    public function removeItem($purchaseId, $itemId) {
        return DB::transaction(function () use ($purchaseId, $itemId) {
            $purchase = $this->model->findOrFail($purchaseId);
            $item = PurchaseItem::where('purchase_id', $purchaseId)->findOrFail($itemId);
            $batch = $item->stockBatch;

            if ($batch && $batch->qty_sisa < $batch->qty_awal) {
                return ResponseHelper::error('Item tidak bisa dihapus karena sebagian stok sudah terpakai', 422);
            }

            $batch?->delete();
            $item->delete();
            $purchase->recalculateTotal();

            return ResponseHelper::success(
                ['purchase_total' => (float) $purchase->fresh()->total],
                'Item removed successfully'
            );
        });
    }

    public function update(array $data, $id) {
        $purchase = $this->model->findOrFail($id);
        $purchase->update($data);
        $purchase->load(['supplier', 'user']);
        return ResponseHelper::success(new PurchaseResource($purchase), 'Purchase updated successfully');
    }

    public function destroy($id) {
        return DB::transaction(function () use ($id) {
            $purchase = $this->model->with('items.stockBatch')->findOrFail($id);

            foreach ($purchase->items as $item) {
                $batch = $item->stockBatch;
                if ($batch && $batch->qty_sisa < $batch->qty_awal) {
                    return ResponseHelper::error('Pembelian tidak bisa dihapus karena sebagian stok sudah terpakai', 422);
                }
                $batch?->delete();
                $item->delete();
            }

            $purchase->forceDelete();
            return ResponseHelper::success(null, 'Purchase deleted successfully');
        });
    }

    public function generateInvoiceNumber(): string {
        $prefix = 'INV';
        $date = date('ymd');
        $latest = $this->model->withTrashed()
            ->where('no_invoice', 'like', "{$prefix}{$date}%")
            ->whereRaw('LENGTH(no_invoice) <= 12')
            ->orderByDesc('no_invoice')
            ->value('no_invoice');

        $nextNum = $latest ? ((int) substr($latest, 9)) + 1 : 1;
        $invoice = sprintf('%s%s%03d', $prefix, $date, $nextNum);

        while ($this->model->withTrashed()->where('no_invoice', $invoice)->exists()) {
            $nextNum++;
            $invoice = sprintf('%s%s%03d', $prefix, $date, $nextNum);
        }

        return $invoice;
    }
}

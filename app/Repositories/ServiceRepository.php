<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Models\ServiceOrder;
use App\Models\ServicePart;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SalesTransaction;
use App\Http\Resources\PaginateResource;
use App\Http\Resources\ServiceOrderResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceRepository
{
    public function __construct(private ServiceOrder $model) {}

    public function index($perPage, $search, $status = null, $startDate = null, $endDate = null, $excludeSudahDiambil = true)
    {
        $query = $this->model->newQuery()
            ->with(['technician', 'serviceBrand'])
            ->withCount('parts')
            ->search($search)
            ->status($status)
            ->dateRange($startDate, $endDate)
            ->statusPengambilan(request('status_pengambilan'));

        if ($excludeSudahDiambil) {
            $query->where('status_pengambilan', '!=', 'sudah_diambil');
        }

        $query->latest('tanggal_masuk')
            ->latest('created_at');

        $data = $query->paginate($perPage);

        return ResponseHelper::success(
            new PaginateResource($data, ServiceOrderResource::class),
            'Service orders retrieved successfully'
        );
    }

    public function show($id)
    {
        $service = $this->model->with(['parts.product', 'salesTransaction', 'technician', 'serviceBrand'])->findOrFail($id);
        return ResponseHelper::success(
            new ServiceOrderResource($service),
            'Service order details retrieved'
        );
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $merkHp = $data['merk_hp'] ?? null;
            if (empty($merkHp) && !empty($data['service_brand_id'])) {
                $brand = \App\Models\ServiceBrand::find($data['service_brand_id']);
                if ($brand) {
                    $merkHp = $brand->nama;
                }
            }

            $service = $this->model->create([
                'nama_pelanggan' => $data['nama_pelanggan'],
                'no_hp_pelanggan' => $data['no_hp_pelanggan'] ?? null,
                'service_brand_id' => $data['service_brand_id'] ?? null,
                'merk_hp' => $merkHp,
                'tipe_hp' => $data['tipe_hp'],
                'kerusakan' => $data['kerusakan'],
                'imei_hp' => $data['imei_hp'] ?? null,
                'kelengkapan' => $data['kelengkapan'] ?? null,
                'biaya_jasa' => $data['biaya_jasa'] ?? 0,
                'technician_id' => $data['technician_id'] ?? null,
                'status' => 'dikerjakan',
                'tanggal_masuk' => $data['tanggal_masuk'] ?? now()->toDateString(),
            ]);
            $service->update([
                'no_service' => strtoupper(substr((string) $service->id, 0, 8)),
            ]);

            if (!empty($data['parts'])) {
                foreach ($data['parts'] as $part) {
                    $product = Product::findOrFail($part['product_id']);

                    // Validate stock
                    if ($product->stok < $part['qty']) {
                        throw new \Exception("Stok {$product->nama} tidak mencukupi.");
                    }

                    ServicePart::create([
                        'service_order_id' => $service->id,
                        'product_id' => $product->id,
                        'nama_part' => $product->nama,
                        'qty' => $part['qty'],
                        'harga_satuan' => $part['harga_satuan'],
                        'subtotal' => $part['qty'] * $part['harga_satuan'],
                    ]);

                    // Deduct stock
                    $product->decrement('stok', $part['qty']);
                }
            }

            return ResponseHelper::success(
                new ServiceOrderResource($service->load('parts.product')),
                'Service order created successfully',
                201
            );
        });
    }

    public function update($id, array $data)
    {
        $service = $this->model->findOrFail($id);

        $updateData = $data;
        if (empty($updateData['merk_hp']) && !empty($updateData['service_brand_id'])) {
            $brand = \App\Models\ServiceBrand::find($updateData['service_brand_id']);
            if ($brand) {
                $updateData['merk_hp'] = $brand->nama;
            }
        }

        $service->update($updateData);

        return ResponseHelper::success(
            new ServiceOrderResource($service->fresh()),
            'Service order updated successfully'
        );
    }

    public function updateStatus($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $service = $this->model->with(['parts.product', 'salesTransaction.items'])->findOrFail($id);
            $updateData = [];
            $restoreParts = array_key_exists('restore_parts', $data)
                ? (bool) $data['restore_parts']
                : true;
            $statusToBatal = array_key_exists('status', $data)
                && $data['status'] === 'batal'
                && $service->status !== 'batal';
            if (array_key_exists('status', $data) && $data['status'] !== null) {
                if ($service->status === 'selesai' && $data['status'] === 'dikerjakan') {
                    throw new \Exception('Status selesai tidak bisa dikembalikan ke proses.');
                }
                if ($service->status_pengambilan === 'sudah_diambil' && $data['status'] !== 'selesai') {
                    throw new \Exception('Service yang sudah diserahkan tidak bisa diubah ke status lain.');
                }
            }

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $updateData['status'] = $data['status'];
                if ($data['status'] === 'selesai' && !$service->tanggal_selesai) {
                    $updateData['tanggal_selesai'] = now()->toDateString();
                }
                if ($data['status'] === 'batal') {
                    $updateData['tanggal_selesai'] = null;
                }
            }

            if (array_key_exists('status_pengambilan', $data) && $data['status_pengambilan'] !== null) {
                $updateData['status_pengambilan'] = $data['status_pengambilan'];
            }

            if (array_key_exists('catatan_teknisi', $data)) {
                $updateData['catatan_teknisi'] = $data['catatan_teknisi'];
            }

            if ($statusToBatal) {
                if ($service->salesTransaction) {
                    $service->salesTransaction->items()->delete();
                    $service->salesTransaction->forceDelete();
                    $updateData['sales_transaction_id'] = null;
                }

                if ($restoreParts) {
                    foreach ($service->parts as $part) {
                        if ($part->product) {
                            $part->product->increment('stok', $part->qty);
                        }
                    }
                    $service->parts()->delete();
                }
                $updateData['status_pengambilan'] = 'belum_diambil';
            }

            $service->update($updateData);

            $hasPaymentPayload = array_key_exists('diskon_persen', $data)
                || array_key_exists('diskon_nominal', $data)
                || array_key_exists('metode_pembayaran', $data)
                || array_key_exists('jumlah_bayar', $data);

            if ($hasPaymentPayload && $service->fresh()->status === 'selesai') {
                if ($service->fresh()->status_pengambilan === 'sudah_diambil') {
                    throw new \Exception('Pembayaran sudah final dan transaksi service telah dikunci.');
                }
                $this->syncServiceTransaction($service->fresh()->load('parts.product', 'salesTransaction'), $data);
                $service->update(['status_pengambilan' => 'sudah_diambil']);
            }

            return ResponseHelper::success(
                new ServiceOrderResource(
                    $service->fresh()->load(['parts.product', 'salesTransaction'])
                ),
                "Status updated successfully"
            );
        });
    }

    private function syncServiceTransaction(ServiceOrder $service, array $payload): void
    {
        $partsSubtotal = (float) $service->parts()->sum('subtotal');
        $subtotal = (float) $service->biaya_jasa + $partsSubtotal;

        $transaction = $service->salesTransaction;
        $diskonPersen = (float) ($payload['diskon_persen'] ?? $transaction?->diskon_persen ?? 0);
        $diskonNominalInput = (float) ($payload['diskon_nominal'] ?? $transaction?->diskon_nominal ?? 0);
        $metodePembayaran = $payload['metode_pembayaran'] ?? $transaction?->metode_pembayaran ?? 'cash';
        $taxPersen = (float) ($transaction?->tax_persen ?? 0);

        $diskonNominal = $diskonPersen > 0
            ? ($subtotal * ($diskonPersen / 100))
            : $diskonNominalInput;
        $afterDiskon = max($subtotal - $diskonNominal, 0);
        $taxNominal = $afterDiskon * ($taxPersen / 100);
        $grandTotal = $afterDiskon + $taxNominal;

        $jumlahBayarInput = (float) ($payload['jumlah_bayar'] ?? $transaction?->jumlah_bayar ?? 0);
        if ($metodePembayaran !== 'cash') {
            $jumlahBayar = $grandTotal;
            $kembalian = 0;
        } else {
            $jumlahBayar = $jumlahBayarInput;
            $kembalian = $jumlahBayar > $grandTotal ? ($jumlahBayar - $grandTotal) : 0;
        }

        $txData = [
            'tanggal' => $service->tanggal_selesai ?? now()->toDateString(),
            'pelanggan' => $service->nama_pelanggan,
            'user_id' => Auth::id() ?? $transaction?->user_id,
            'sales_rep_id' => null,
            'subtotal' => $subtotal,
            'diskon_persen' => $diskonPersen,
            'diskon_nominal' => $diskonNominal,
            'tax_id' => null,
            'tax_persen' => $taxPersen,
            'tax_nominal' => $taxNominal,
            'grand_total' => $grandTotal,
            'metode_pembayaran' => $metodePembayaran,
            'jumlah_bayar' => $jumlahBayar,
            'kembalian' => $kembalian,
            'tipe' => 'service',
        ];

        if (!$transaction) {
            $transaction = SalesTransaction::create(array_merge($txData, [
                'no_invoice' => $this->generateServiceInvoiceNumber(),
            ]));
            $service->update(['sales_transaction_id' => $transaction->id]);
        } else {
            $transaction->update($txData);
            $transaction->items()->delete();
        }

        foreach ($service->parts as $part) {
            SaleItem::create([
                'sales_transaction_id' => $transaction->id,
                'product_id' => $part->product_id,
                'qty' => $part->qty,
                'harga_satuan' => $part->harga_satuan,
                'subtotal' => $part->subtotal,
                'hpp_total' => (float) (($part->product?->harga_modal ?? 0) * $part->qty),
            ]);
        }
    }

    private function generateServiceInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = date('ymd');

        $latest = SalesTransaction::withTrashed()
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

        while (SalesTransaction::withTrashed()->where('no_invoice', $invoice)->exists()) {
            $nextNum++;
            $invoice = sprintf('%s%s%03d', $prefix, $date, $nextNum);
        }

        return $invoice;
    }

    public function addPart($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $service = $this->model->findOrFail($id);
            $product = Product::findOrFail($data['product_id']);

            // Validate stock
            if ($product->stok < $data['qty']) {
                throw new \Exception("Stok {$product->nama} tidak mencukupi.");
            }

            $part = ServicePart::create([
                'service_order_id' => $service->id,
                'product_id' => $product->id,
                'nama_part' => $product->nama,
                'qty' => $data['qty'],
                'harga_satuan' => $data['harga_satuan'] ?? $product->harga_jual,
                'subtotal' => $data['qty'] * ($data['harga_satuan'] ?? $product->harga_jual),
            ]);

            // Deduct stock
            $product->decrement('stok', $data['qty']);

            return ResponseHelper::success(null, 'Part added successfully');
        });
    }

    public function removePart($id, $partId)
    {
        return DB::transaction(function () use ($id, $partId) {
            $part = ServicePart::where('service_order_id', $id)->findOrFail($partId);
            $product = $part->product;

            // Restore stock
            if ($product) {
                $product->increment('stok', $part->qty);
            }

            $part->delete();

            return ResponseHelper::success(null, 'Part removed successfully');
        });
    }

    public function destroy($id)
    {
        $service = $this->model->findOrFail($id);

        // If has parts, restore stock? Or prevent deletion?
        // Typically prevent deletion if completed.
        /** @var \App\Models\User|null $authuser */
        $authuser = \Illuminate\Support\Facades\Auth::user();
        if (!$authuser || !$authuser->hasRole('super-admin')) {
            if ($service->status === 'selesai' || $service->status_pengambilan === 'sudah_diambil') {
                throw new \Exception("Service yang sudah selesai / sudah diambil tidak dapat dihapus.");
            }
        }

        return DB::transaction(function () use ($service) {
            foreach ($service->parts as $part) {
                if ($part->product) {
                    $part->product->increment('stok', $part->qty);
                }
                $part->delete();
            }
            $service->delete();
            return ResponseHelper::success(null, 'Service order deleted successfully');
        });
    }
}

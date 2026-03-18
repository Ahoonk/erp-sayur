<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockBatch;
use Illuminate\Support\Facades\DB;

class ExpiredStockService
{
    public function applyExpiredBatches(?string $today = null): int
    {
        $today = $today ?? now()->toDateString();

        return DB::transaction(function () use ($today) {
            $batches = StockBatch::query()
                ->whereNotNull('expired_at')
                ->where('expired_at', '<=', $today)
                ->where('qty_sisa', '>', 0)
                ->orderBy('expired_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $created = 0;

            foreach ($batches as $batch) {
                $qty = (float) $batch->qty_sisa;
                if ($qty <= 0) {
                    continue;
                }

                $batch->qty_sisa = 0;
                $batch->save();

                StockAdjustment::create([
                    'katalog_barang_id' => $batch->katalog_barang_id,
                    'tanggal' => $batch->expired_at?->format('Y-m-d') ?? $today,
                    'reason' => 'EXPIRED',
                    'qty' => $qty,
                    'keterangan' => 'Auto expired',
                    'created_by' => null,
                ]);

                $created++;
            }

            return $created;
        });
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestSaleItem = null;
        if (
            $this->relationLoaded('product') &&
            $this->product &&
            $this->product->relationLoaded('saleItems')
        ) {
            $latestSaleItem = $this->product->saleItems
                ->sortByDesc('created_at')
                ->first();
        }

        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'product_id' => $this->product_id,
            'qty' => $this->qty,
            'harga_beli' => (float) $this->harga_beli,
            'subtotal' => (float) $this->subtotal,

            // Included Purchase details
            'purchase' => $this->whenLoaded('purchase', fn() => [
                'id' => $this->purchase->id,
                'no_invoice' => $this->purchase->no_invoice,
                'tanggal' => $this->purchase->tanggal?->format('Y-m-d'),
                'supplier' => $this->purchase->supplier ? [
                    'id' => $this->purchase->supplier->id,
                    'nama' => $this->purchase->supplier->nama,
                ] : null,
            ]),

            // Included Product details
            'product' => $this->whenLoaded('product', fn() => [
                'id' => $this->product->id,
                'barcode' => $this->product->barcode,
                'nama' => $this->product->masterProduct?->nama,
                'imei1' => $this->product->imei1,
                'imei2' => $this->product->imei2,
                'stok' => $this->product->stok,
                'harga_jual' => (float) $this->product->harga_jual,
                'keterangan' => $this->product->keterangan,
                'brand' => $this->product->masterProduct?->brand?->nama,
                'brand_id' => $this->product->masterProduct?->brand_id,
                'grade' => $this->product->grade?->nama,
                'grade_id' => $this->product->grade_id,
                'unit' => $this->product->unit?->nama,
                'unit_id' => $this->product->unit_id,
                'foto' => $this->product->foto,
                'is_sold' => $this->product->saleItems?->isNotEmpty() ?? false,
                'harga_jual_transaksi' => (float) ($latestSaleItem?->harga_satuan ?? 0),
                'sale' => $latestSaleItem?->salesTransaction ? [
                    'no_invoice' => $latestSaleItem->salesTransaction->no_invoice,
                    'tanggal' => $latestSaleItem->salesTransaction->tanggal?->format('Y-m-d'),
                ] : null,
            ]),
        ];
    }
}

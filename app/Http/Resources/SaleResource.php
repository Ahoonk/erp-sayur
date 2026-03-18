<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_invoice' => $this->no_invoice,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'pelanggan' => $this->pelanggan,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'sales_rep' => $this->when(
                $this->relationLoaded('salesRep') || $this->relationLoaded('salesUser'),
                function () {
                    $sales = $this->salesRep ?: $this->salesUser;
                    if (!$sales) {
                        return null;
                    }

                    return [
                        'id' => $sales->id,
                        'nama' => $sales->nama ?? $sales->name ?? null,
                    ];
                }
            ),
            'subtotal' => (float) $this->subtotal,
            'diskon_persen' => (float) $this->diskon_persen,
            'diskon_nominal' => (float) $this->diskon_nominal,
            'tax' => $this->whenLoaded('tax', fn() => [
                'id' => $this->tax->id,
                'nama' => $this->tax->nama,
            ]),
            'tax_persen' => (float) $this->tax_persen,
            'tax_nominal' => (float) $this->tax_nominal,
            'grand_total' => (float) $this->grand_total,
            'metode_pembayaran' => $this->metode_pembayaran,
            'jumlah_bayar' => (float) $this->jumlah_bayar,
            'kembalian' => (float) $this->kembalian,
            'tipe' => $this->tipe,
            'service_order' => $this->whenLoaded('serviceOrder', function () {
                if (!$this->serviceOrder) {
                    return null;
                }

                return [
                    'id' => $this->serviceOrder->id,
                    'no_service' => strtoupper(substr((string) $this->serviceOrder->id, 0, 8)),
                    'technician' => $this->serviceOrder->technician ? [
                        'id' => $this->serviceOrder->technician->id,
                        'nama' => $this->serviceOrder->technician->nama,
                    ] : null,
                    'biaya_jasa' => (float) ($this->serviceOrder->biaya_jasa ?? 0),
                    'parts' => $this->serviceOrder->parts->map(fn($part) => [
                        'id' => $part->id,
                        'nama_part' => $part->nama_part,
                        'qty' => (int) $part->qty,
                        'subtotal' => (float) $part->subtotal,
                    ])->values(),
                ];
            }),
            'items' => $this->whenLoaded(
                'items',
                fn() =>
                $this->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'barcode' => $item->product->barcode,
                        'nama' => $item->product->masterProduct?->nama,
                        'imei1' => $item->product->imei1,
                        'imei2' => $item->product->imei2,
                        'brand' => $item->product->masterProduct?->brand?->nama,
                        'unit' => $item->product->unit?->nama,
                        'grade' => $item->product->grade?->nama,
                        'identifier_type' => 'none',
                        'foto' => $item->product->foto,
                    ] : null,
                    'qty' => $item->qty,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'subtotal' => (float) $item->subtotal,
                    'hpp_total' => (float) ($item->hpp_total ?? 0),
                ])
            ),
            'items_count' => $this->whenCounted('items'),
            'items_sum_qty' => $this->whenHas('items_sum_qty'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

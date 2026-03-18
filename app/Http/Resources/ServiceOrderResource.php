<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_service' => strtoupper(substr((string) $this->id, 0, 8)),
            'sales_transaction_id' => $this->sales_transaction_id,
            'technician_id' => $this->technician_id,
            'service_brand_id' => $this->service_brand_id,
            'nama_pelanggan' => $this->nama_pelanggan,
            'no_hp_pelanggan' => $this->no_hp_pelanggan,
            'merk_hp' => $this->serviceBrand ? $this->serviceBrand->nama : $this->merk_hp,
            'tipe_hp' => $this->tipe_hp,
            'kerusakan' => $this->kerusakan,
            'imei_hp' => $this->imei_hp,
            'kelengkapan' => $this->kelengkapan,
            'biaya_jasa' => (float) $this->biaya_jasa,
            'status' => $this->status,
            'status_pengambilan' => $this->status_pengambilan,
            'tanggal_masuk' => $this->tanggal_masuk?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'catatan_teknisi' => $this->catatan_teknisi,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // Totals
            'total_biaya_parts' => (float) $this->parts()->sum('subtotal'),
            'grand_total' => (float) $this->biaya_jasa + (float) $this->parts()->sum('subtotal'),

            // Relationships
            'parts' => $this->whenLoaded('parts', fn() => $this->parts->map(fn($p) => [
                'id' => $p->id,
                'nama_part' => $p->nama_part,
                'product_id' => $p->product_id,
                'qty' => $p->qty,
                'harga_satuan' => (float) $p->harga_satuan,
                'subtotal' => (float) $p->subtotal,
                'product' => $p->product ? [
                    'id' => $p->product->id,
                    'nama' => $p->product->nama,
                    'barcode' => $p->product->barcode,
                ] : null,
            ])),

            'transaction' => $this->whenLoaded('salesTransaction', fn() => [
                'id' => $this->salesTransaction->id,
                'no_invoice' => $this->salesTransaction->no_invoice,
                'tanggal' => optional($this->salesTransaction->tanggal)->format('Y-m-d'),
                'subtotal' => (float) $this->salesTransaction->subtotal,
                'diskon_persen' => (float) $this->salesTransaction->diskon_persen,
                'diskon_nominal' => (float) $this->salesTransaction->diskon_nominal,
                'tax_persen' => (float) $this->salesTransaction->tax_persen,
                'tax_nominal' => (float) $this->salesTransaction->tax_nominal,
                'grand_total' => (float) $this->salesTransaction->grand_total,
                'metode_pembayaran' => $this->salesTransaction->metode_pembayaran,
                'jumlah_bayar' => (float) $this->salesTransaction->jumlah_bayar,
                'kembalian' => (float) $this->salesTransaction->kembalian,
            ]),

            'technician' => $this->whenLoaded('technician', fn() => $this->technician ? [
                'id' => $this->technician->id,
                'nama' => $this->technician->nama,
            ] : null),
            'service_brand' => $this->whenLoaded('serviceBrand', fn() => $this->serviceBrand ? [
                'id' => $this->serviceBrand->id,
                'nama' => $this->serviceBrand->nama,
            ] : null),
        ];
    }
}

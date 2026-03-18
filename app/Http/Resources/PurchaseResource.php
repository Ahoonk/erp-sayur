<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class PurchaseResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'no_invoice' => $this->no_invoice,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn() => ['id' => $this->supplier->id, 'nama' => $this->supplier->nama]),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => ['id' => $this->user->id, 'name' => $this->user->name]),
            'total' => (float) $this->total,
            'keterangan' => $this->keterangan,
            'items_count' => $this->whenCounted('items', fn() => $this->items_count),
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'katalog_barang_id' => $item->katalog_barang_id,
                'katalog_barang' => $item->relationLoaded('katalogBarang') ? [
                    'id' => $item->katalogBarang->id,
                    'kode_barang' => $item->katalogBarang->kode_barang,
                    'nama_barang' => $item->katalogBarang->nama_barang,
                    'unit' => $item->katalogBarang->unit ? ['id' => $item->katalogBarang->unit->id, 'nama' => $item->katalogBarang->unit->nama] : null,
                    'category' => $item->katalogBarang->category ? ['id' => $item->katalogBarang->category->id, 'nama' => $item->katalogBarang->category->nama] : null,
                ] : null,
                'qty' => (float) $item->qty,
                'harga_beli' => (float) $item->harga_beli,
                'subtotal' => (float) $item->subtotal,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}

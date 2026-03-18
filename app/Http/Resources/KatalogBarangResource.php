<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class KatalogBarangResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn() => ['id' => $this->category->id, 'nama' => $this->category->nama, 'kode_prefix' => $this->category->kode_prefix]),
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn() => ['id' => $this->unit->id, 'nama' => $this->unit->nama]),
            'total_stok' => (float) $this->total_stok,
            'modal_rata_rata' => (float) $this->modal_rata_rata,
            'created_at' => $this->created_at,
        ];
    }
}

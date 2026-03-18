<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'nama' => $this->masterProduct?->nama,
            'master_product_id' => $this->master_product_id,
            'master_product' => $this->whenLoaded('masterProduct', fn() => [
                'id' => $this->masterProduct->id,
                'nama' => $this->masterProduct->nama,
                'brand_id' => $this->masterProduct->brand_id,
            ]),
            'brand' => $this->masterProduct?->brand ? [
                'id' => $this->masterProduct->brand->id,
                'nama' => $this->masterProduct->brand->nama,
            ] : null,
            'unit' => $this->unit ? [
                'id' => $this->unit->id,
                'nama' => $this->unit->nama,
            ] : null,
            'grade' => $this->whenLoaded('grade', fn() => $this->grade ? [
                'id' => $this->grade->id,
                'nama' => $this->grade->nama,
            ] : null),
            'grade_id' => $this->grade_id,
            'imei1' => $this->imei1,
            'imei2' => $this->imei2,
            'harga_modal' => (float) $this->harga_modal,
            'harga_jual' => (float) $this->harga_jual,
            'stok' => $this->stok,
            'keterangan' => $this->keterangan,
            'foto' => $this->foto,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

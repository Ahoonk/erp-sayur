<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class MitraResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
            'keterangan' => $this->keterangan,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}

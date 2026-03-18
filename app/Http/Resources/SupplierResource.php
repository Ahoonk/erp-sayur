<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'phone' => $this->phone,
            'email' => $this->email,
            'alamat' => $this->alamat,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

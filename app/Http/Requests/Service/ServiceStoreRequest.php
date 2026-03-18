<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => 'required|string|max:255',
            'no_hp_pelanggan' => 'nullable|string|max:30',
            'service_brand_id' => 'nullable|uuid|exists:service_brands,id',
            'merk_hp' => 'nullable|string|max:255',
            'tipe_hp' => 'required|string|max:255',
            'kerusakan' => 'required|string',
            'imei_hp' => 'nullable|string|max:50',
            'kelengkapan' => 'nullable|string|max:1000',
            'biaya_jasa' => 'nullable|numeric|min:0',
            'tanggal_masuk' => 'nullable|date',
            'technician_id' => 'nullable|uuid|exists:technicians,id',
            'parts' => 'nullable|array',
            'parts.*.product_id' => 'required|uuid|exists:products,id',
            'parts.*.qty' => 'required|integer|min:1',
            'parts.*.harga_satuan' => 'required|numeric|min:0',
        ];
    }
}

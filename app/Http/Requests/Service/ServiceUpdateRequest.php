<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => 'sometimes|string|max:255',
            'no_hp_pelanggan' => 'nullable|string|max:30',
            'merk_hp' => 'sometimes|string|max:255',
            'tipe_hp' => 'sometimes|string|max:255',
            'kerusakan' => 'sometimes|string',
            'imei_hp' => 'nullable|string|max:50',
            'kelengkapan' => 'nullable|string|max:1000',
            'biaya_jasa' => 'sometimes|numeric|min:0',
            'technician_id' => 'nullable|uuid|exists:technicians,id',
            'status' => 'sometimes|in:pending,dikerjakan,selesai,batal',
            'status_pengambilan' => 'sometimes|in:belum_diambil,sudah_diambil',
            'catatan_teknisi' => 'nullable|string',
        ];
    }
}


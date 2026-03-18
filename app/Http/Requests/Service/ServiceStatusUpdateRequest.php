<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:pending,dikerjakan,selesai,batal',
            'status_pengambilan' => 'nullable|in:belum_diambil,sudah_diambil',
            'catatan_teknisi' => 'nullable|string',
            'restore_parts' => 'nullable|boolean',
            'diskon_persen' => 'nullable|numeric|min:0|max:100',
            'diskon_nominal' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'nullable|in:cash,transfer,qris',
            'jumlah_bayar' => 'nullable|numeric|min:0',
        ];
    }
}

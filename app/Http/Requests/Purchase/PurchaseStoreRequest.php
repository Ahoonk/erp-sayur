<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_invoice'  => 'required|string|max:100|unique:purchases,no_invoice',
            'tanggal'     => 'required|date',
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'keterangan'  => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'no_invoice.required'  => 'No. Invoice wajib diisi.',
            'no_invoice.unique'    => 'No. Invoice sudah digunakan.',
            'no_invoice.max'       => 'No. Invoice maksimal 100 karakter.',
            'tanggal.required'     => 'Tanggal wajib diisi.',
            'tanggal.date'         => 'Format tanggal tidak valid.',
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'supplier_id.exists'   => 'Supplier tidak ditemukan.',
        ];
    }
}

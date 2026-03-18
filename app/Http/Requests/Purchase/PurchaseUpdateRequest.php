<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('purchase') ?? $this->route('id');

        return [
            'no_invoice'  => 'sometimes|string|max:100|unique:purchases,no_invoice,' . $id,
            'tanggal'     => 'sometimes|date',
            'supplier_id' => 'sometimes|uuid|exists:suppliers,id',
            'keterangan'  => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'no_invoice.unique'    => 'No. Invoice sudah digunakan.',
            'no_invoice.max'       => 'No. Invoice maksimal 100 karakter.',
            'tanggal.date'         => 'Format tanggal tidak valid.',
            'supplier_id.exists'   => 'Supplier tidak ditemukan.',
        ];
    }
}

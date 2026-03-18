<?php

namespace App\Http\Requests\Purchase;

use App\Models\MasterProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PurchaseItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_product_id' => 'required_without:product_id|nullable|uuid|exists:master_products,id',
            'nama'              => 'nullable|string|max:255',
            'brand_id'          => 'nullable|uuid|exists:brands,id',
            'category_id'       => 'nullable|uuid|exists:categories,id',
            'grade_id'          => 'nullable|uuid|exists:grades,id',
            'unit_id'           => 'nullable|uuid|exists:units,id',
            'imei1'             => 'nullable|string|max:20',
            'imei2'             => 'nullable|string|max:20',
            'barcode'           => 'nullable|string|max:120',
            'harga_beli'        => 'required|numeric|min:0',
            'harga_jual'        => 'nullable|numeric|min:0',
            'qty'               => 'required|integer|min:1|max:9999',
            'keterangan'        => 'nullable|string|max:500',
            'product_id'        => 'nullable|uuid|exists:products,id',
            'foto'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'       => 'Nama produk wajib diisi.',
            'nama.max'            => 'Nama produk maksimal 255 karakter.',
            'brand_id.exists'     => 'Merk tidak ditemukan.',
            'category_id.exists'  => 'Kategori tidak ditemukan.',
            'grade_id.exists'     => 'Grade tidak ditemukan.',
            'unit_id.exists'      => 'Satuan tidak ditemukan.',
            'imei1.max'           => 'IMEI 1 maksimal 20 karakter.',
            'imei2.max'           => 'IMEI 2 maksimal 20 karakter.',
            'harga_beli.required' => 'Harga beli wajib diisi.',
            'harga_beli.numeric'  => 'Harga beli harus berupa angka.',
            'harga_beli.min'      => 'Harga beli tidak boleh negatif.',
            'harga_jual.numeric'  => 'Harga jual harus berupa angka.',
            'qty.integer'         => 'Qty harus berupa bilangan bulat.',
            'qty.min'             => 'Qty minimal 1.',
            'qty.max'             => 'Qty maksimal 9999.',
            'foto.image'          => 'File harus berupa gambar.',
            'foto.mimes'          => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'foto.max'            => 'Ukuran gambar maksimal 2MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('product_id')) {
                return;
            }

            $masterProductId = $this->input('master_product_id');
            if (!$masterProductId) {
                return;
            }

            $masterProduct = MasterProduct::find($masterProductId);
            if (!$masterProduct) {
                return;
            }

            $identifierType = $masterProduct->identifier_type ?? 'none';
            $qty = (int) $this->input('qty', 1);

            if (in_array($identifierType, ['imei1', 'imei2', 'serial'], true) && $qty !== 1) {
                $validator->errors()->add('qty', 'Qty harus 1 untuk produk yang menggunakan IMEI/Serial Number.');
            }
        });
    }
}

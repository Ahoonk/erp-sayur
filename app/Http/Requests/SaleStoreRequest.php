<?php

namespace App\Http\Requests;

use App\Models\SalesRep;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SaleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => 'nullable|date',
            'pelanggan' => 'nullable|string|max:100',
            'user_id' => 'nullable|uuid|exists:users,id',
            'sales_rep_id' => [
                'nullable',
                'uuid',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }

                    $existsInSalesReps = SalesRep::query()->whereKey($value)->exists();
                    if ($existsInSalesReps) {
                        return;
                    }

                    $user = User::query()->whereKey($value)->with('roles')->first();
                    if (!$user) {
                        $fail('Sales yang dipilih tidak valid.');
                        return;
                    }

                    $isSuperAdmin = $user->roles->contains(fn($role) => $role->name === 'super-admin');
                    if ($isSuperAdmin) {
                        $fail('User super-admin tidak bisa dipilih sebagai sales.');
                    }
                },
            ],
            'tax_id' => 'nullable|uuid|exists:taxes,id',
            'tax_persen' => 'nullable|numeric|min:0|max:100',
            'diskon_persen' => 'nullable|numeric|min:0|max:100',
            'diskon_nominal' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'required|in:cash,transfer,qris',
            'jumlah_bayar' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    public function index()
    {
        $setting = StoreSetting::first();
        if (!$setting) {
            $setting = StoreSetting::create([
                'name' => 'CV DHAROES PUTRA',
                'address' => 'Jl. Pertanian Raya No 61 C, Kel. Lebak Bulus, Kec. Cilandak, Kota Jakarta Selatan',
                'phone' => '0878-7185-8222',
                'email' => 'bryangadget@gmail.com',
                'footer_text' => 'Terima Kasih Atas Pembelian Anda',
                'service_terms' => "- Tanda terima ini wajib dibawa saat pengambilan unit.\n- Barang yang tidak diambil dalam 30 hari diluar tanggung jawab kami.\n- Garansi service 7 hari hanya berlaku untuk kerusakan yang sama.\n- Pihak toko tidak bertanggung jawab atas kehilangan data pada unit.",
            ]);
        }

        if ($setting->logo) {
            $setting->logo_url = asset('storage/' . $setting->logo);
        } else {
            $setting->logo_url = null;
        }

        if ($setting->qris_image) {
            $setting->qris_image_url = asset('storage/' . $setting->qris_image);
        } else {
            $setting->qris_image_url = null;
        }

        return ResponseHelper::success($setting, 'Store settings retrieved successfully');
    }

    public function logo()
    {
        $setting = StoreSetting::first();
        if (!$setting) {
            $setting = StoreSetting::create([
                'name' => 'CV DHAROES PUTRA',
                'address' => 'Jl. Pertanian Raya No 61 C, Kel. Lebak Bulus, Kec. Cilandak, Kota Jakarta Selatan',
                'phone' => '0878-7185-8222',
                'email' => 'bryangadget@gmail.com',
                'footer_text' => 'Terima Kasih Atas Pembelian Anda',
                'service_terms' => "- Tanda terima ini wajib dibawa saat pengambilan unit.\n- Barang yang tidak diambil dalam 30 hari diluar tanggung jawab kami.\n- Garansi service 7 hari hanya berlaku untuk kerusakan yang sama.\n- Pihak toko tidak bertanggung jawab atas kehilangan data pada unit.",
            ]);
        }

        $name = $setting->name;
        $logo_url = $setting->logo ? asset('storage/' . $setting->logo) : null;

        return ResponseHelper::success([
            'name' => $name,
            'logo_url' => $logo_url
        ], 'Store logo retrieved successfully');
    }

    public function update(Request $request)
    {
        $setting = StoreSetting::first();
        if (!$setting) {
            $setting = new StoreSetting();
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'footer_text' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'signature_name' => 'nullable|string',
            'service_terms' => 'nullable|string',
            'qris_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $data = $request->only([
            'name',
            'email',
            'phone',
            'address',
            'footer_text',
            'bank_name',
            'bank_account',
            'bank_account_name',
            'signature_name',
            'service_terms'
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $path = $request->file('logo')->store('store', 'public');
            $data['logo'] = $path;
        }

        if ($request->hasFile('qris_image')) {
            // Delete old qris
            if ($setting->qris_image) {
                Storage::disk('public')->delete($setting->qris_image);
            }
            $path = $request->file('qris_image')->store('store', 'public');
            $data['qris_image'] = $path;
        }

        if ($setting->exists) {
            $setting->update($data);
        } else {
            $setting = StoreSetting::create($data);
        }

        return ResponseHelper::success($setting, 'Store settings updated successfully');
    }
}

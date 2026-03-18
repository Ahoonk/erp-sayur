<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SparepartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = \App\Models\Category::firstOrCreate(['nama' => 'Sparepart']);
        $brand = \App\Models\Brand::firstOrCreate(['nama' => 'OEM']);
        $unit = \App\Models\Unit::firstOrCreate(['nama' => 'Pcs']);
        $grade = \App\Models\Grade::firstOrCreate(['nama' => 'Original']);

        $parts = [
            ['nama' => 'LCD Samsung S23 Ultra', 'harga_modal' => 2000000, 'harga_jual' => 2500000],
            ['nama' => 'Baterai iPhone 13 Pro', 'harga_modal' => 450000, 'harga_jual' => 650000],
            ['nama' => 'Konektor Charger Xiaomi Poco F3', 'harga_modal' => 50000, 'harga_jual' => 150000],
            ['nama' => 'IC EMMC 64GB', 'harga_modal' => 120000, 'harga_jual' => 300000],
            ['nama' => 'LCD iPhone 11', 'harga_modal' => 400000, 'harga_jual' => 700000],
            ['nama' => 'Kamera Belakang Samsung A52', 'harga_modal' => 150000, 'harga_jual' => 350000],
        ];

        foreach ($parts as $part) {
            \App\Models\Product::updateOrCreate(
                ['nama' => $part['nama']], // check by name
                [
                    'barcode' => 'SP-' . substr(md5(uniqid()), 0, 6) . mt_rand(100, 999),
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'unit_id' => $unit->id,
                    'grade_id' => $grade->id,
                    'harga_modal' => $part['harga_modal'],
                    'harga_jual' => $part['harga_jual'],
                    'stok' => 10,
                    'keterangan' => 'Dummy Sparepart',
                ]
            );
        }
    }
}

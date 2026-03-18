<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Grade;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = Brand::all();
        $categories = Category::all();
        $grades = Grade::all();
        $units = Unit::all();

        if ($brands->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Please run MasterDataSeeder first!');
            return;
        }

        $products = [
            ['nama' => 'Samsung Galaxy S24 Ultra', 'harga_modal' => 15000000, 'harga_jual' => 18000000],
            ['nama' => 'iPhone 15 Pro Max', 'harga_modal' => 20000000, 'harga_jual' => 24000000],
            ['nama' => 'Xiaomi 14', 'harga_modal' => 8000000, 'harga_jual' => 10000000],
            ['nama' => 'OPPO Reno 11 Pro', 'harga_modal' => 6000000, 'harga_jual' => 8000000],
            ['nama' => 'Vivo V30 Pro', 'harga_modal' => 5500000, 'harga_jual' => 7500000],
            ['nama' => 'Realme 12 Pro+', 'harga_modal' => 4500000, 'harga_jual' => 6000000],
            ['nama' => 'Infinix Note 40 Pro', 'harga_modal' => 2500000, 'harga_jual' => 3500000],
            ['nama' => 'iPad Air M2', 'harga_modal' => 9000000, 'harga_jual' => 11000000],
            ['nama' => 'Samsung Tab S9', 'harga_modal' => 10000000, 'harga_jual' => 12500000],
            ['nama' => 'MacBook Air M3', 'harga_modal' => 15000000, 'harga_jual' => 18500000],
        ];

        foreach ($products as $p) {
            Product::create([
                'barcode' => '880' . rand(1000000, 9999999),
                'nama' => $p['nama'],
                'brand_id' => $brands->random()->id,
                'category_id' => $categories->random()->id,
                'grade_id' => $grades->random()->id,
                'unit_id' => $units->random()->id,
                'imei1' => '35' . rand(1000000000000, 9999999999999),
                'imei2' => '35' . rand(1000000000000, 9999999999999),
                'harga_modal' => $p['harga_modal'],
                'harga_jual' => $p['harga_jual'],
                'stok' => 0, // Will be filled by PurchaseSeeder
                'keterangan' => 'Sample product from Seeder',
            ]);
        }

        $this->command->info('✓ 10 products created.');
    }
}

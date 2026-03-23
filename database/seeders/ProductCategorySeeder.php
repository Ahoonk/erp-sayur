<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Sembako',
            'Bahan Hasil Bumi',
            'Frozen Food',
            'Hasil Ternak',
            'Bumbu dan Rempah',
            'Minuman',
        ];

        foreach ($categories as $name) {
            ProductCategory::firstOrCreate(['name' => $name]);
        }
    }
}
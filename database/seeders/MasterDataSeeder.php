<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed initial master data for ERP Sayur system.
     */
    public function run(): void
    {
        // === Categories (Kategori) ===
        // kode_prefix: V=Sayur, F=Buah, R=Beras, A=Ayam/Daging, D=Dry Good/Grosir/Monouse/Cleaning
        $categories = [
            ['nama' => 'Sayur',                               'kode_prefix' => 'V'],
            ['nama' => 'Buah',                                'kode_prefix' => 'F'],
            ['nama' => 'Beras',                               'kode_prefix' => 'R'],
            ['nama' => 'Ayam Daging dan Olahan',              'kode_prefix' => 'A'],
            ['nama' => 'Dry Good Grosir Monouse Cleaning',    'kode_prefix' => 'D'],
            ['nama' => 'Frozen Food',                         'kode_prefix' => 'FF'],
        ];
        foreach ($categories as $category) {
            Category::firstOrCreate(['nama' => $category['nama']], $category);
        }
        $this->command->info('✓ ' . count($categories) . ' categories created');

        // === Units (Satuan) ===
        $units = [
            'KG', 'PACK', 'IKAT', 'BKS', 'BTL', 'CTN',
            'KRG', 'BAL', 'PAIL', 'GLN', 'BOX', 'LTR',
            'SCT', 'RTG', 'SLOP', 'EKOR',
        ];
        foreach ($units as $unit) {
            Unit::firstOrCreate(['nama' => $unit]);
        }
        $this->command->info('✓ ' . count($units) . ' units created');

        // === Suppliers ===
        $suppliers = [
            ['nama' => 'Supplier Umum', 'phone' => null, 'email' => null, 'alamat' => null],
        ];
        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['nama' => $supplier['nama']], $supplier);
        }
        $this->command->info('✓ ' . count($suppliers) . ' suppliers created');
    }
}

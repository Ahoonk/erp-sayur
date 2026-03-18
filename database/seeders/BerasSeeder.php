<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\KatalogBarang;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class BerasSeeder extends Seeder
{
    public function run(): void
    {
        $beras = Category::where('nama', 'Beras')->first();
        if (!$beras) {
            $this->command->error('Category "Beras" not found. Run MasterDataSeeder first.');
            return;
        }

        Unit::firstOrCreate(['nama' => 'KG']);
        $unit = Unit::where('nama', 'KG')->first();
        if (!$unit) {
            $this->command->error('Unit "KG" not found.');
            return;
        }

        $items = [
            ['kode' => 'R001', 'nama' => 'BERAS "ANGGUR" @25kg'],
            ['kode' => 'R002', 'nama' => 'BERAS "LOKAL STANDARD" @25kg'],
            ['kode' => 'R003', 'nama' => 'BERAS "PANDAN WANGI" @25kg'],
            ['kode' => 'R004', 'nama' => 'BERAS "PIRING NASI" @20kg'],
            ['kode' => 'R005', 'nama' => 'BERAS "ROJO LELE ORI" @25kg'],
            ['kode' => 'R006', 'nama' => 'BERAS "SETRA RAMOS" @20kg'],
            ['kode' => 'R007', 'nama' => 'BERAS IR "LUMBUNG DESA" @20kg'],
            ['kode' => 'R008', 'nama' => 'BERAS "PANDAN WANGI" @15kg'],
        ];

        foreach ($items as $item) {
            KatalogBarang::updateOrCreate(
                ['kode_barang' => $item['kode']],
                [
                    'nama_barang' => $item['nama'],
                    'category_id' => $beras->id,
                    'unit_id' => $unit->id,
                ]
            );
        }

        $this->command->info('✓ ' . count($items) . ' beras items inserted/updated');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\KatalogBarang;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class FrozenFoodSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::where('nama', 'Frozen Food')->first();
        if (!$category) {
            $this->command->error('Category "Frozen Food" not found. Run MasterDataSeeder first.');
            return;
        }

        $unitNames = ['KG', 'PACK', 'EKOR'];
        foreach ($unitNames as $unitName) {
            Unit::firstOrCreate(['nama' => $unitName]);
        }
        $units = Unit::whereIn('nama', $unitNames)->get()->keyBy('nama');
        if (!$units->has('KG') || !$units->has('PACK') || !$units->has('EKOR')) {
            $this->command->error('Units "KG", "PACK", and/or "EKOR" not found.');
            return;
        }

        $items = [
            ['kode' => 'FF001', 'nama' => 'Frozen - AYAM CEKER', 'unit' => 'KG'],
            ['kode' => 'FF002', 'nama' => 'Frozen - AYAM PAHA DRUM STIK', 'unit' => 'KG'],
            ['kode' => 'FF003', 'nama' => 'Frozen - AYAM SAYAP', 'unit' => 'KG'],
            ['kode' => 'FF004', 'nama' => 'Frozen - AYAM UTUH (07-08 /kg)', 'unit' => 'EKOR'],
            ['kode' => 'FF005', 'nama' => 'Frozen - AYAM UTUH (09-1 /kg)', 'unit' => 'EKOR'],
            ['kode' => 'FF006', 'nama' => 'Frozen - AYAM UTUH (1.1-1.2 /kg)', 'unit' => 'KG'],
            ['kode' => 'FF007', 'nama' => 'Frozen - AYAM UTUH (1.2-1.3 /kg)', 'unit' => 'KG'],
            ['kode' => 'FF008', 'nama' => 'Frozen - AYAM FILET PAHA TANPA KULIT', 'unit' => 'KG'],
            ['kode' => 'FF009', 'nama' => 'Frozen - AYAM FILET DADA TANPA KULIT', 'unit' => 'KG'],
            ['kode' => 'FF010', 'nama' => 'Frozen - AYAM GILING', 'unit' => 'KG'],
            ['kode' => 'FF011', 'nama' => 'Frozen - CUMI (TUBE)', 'unit' => 'KG'],
            ['kode' => 'FF012', 'nama' => 'Frozen - DAGING BLADE', 'unit' => 'KG'],
            ['kode' => 'FF013', 'nama' => 'Frozen - DAGING GILLING', 'unit' => 'KG'],
            ['kode' => 'FF014', 'nama' => 'Frozen - DAGING TOP SIDE (1kg)', 'unit' => 'KG'],
            ['kode' => 'FF015', 'nama' => 'Frozen - DAGING TOP SIDE (5kg+)', 'unit' => 'KG'],
            ['kode' => 'FF016', 'nama' => 'Frozen - IKAN DORI FILLET GLZ: 40-50%', 'unit' => 'KG'],
            ['kode' => 'FF017', 'nama' => 'Frozen - IKAN DORI FILLET GLZ: 5-10%', 'unit' => 'KG'],
            ['kode' => 'FF018', 'nama' => 'Frozen - IGA SHORT RIBS POTONG 10-12cm', 'unit' => 'KG'],
            ['kode' => 'FF019', 'nama' => 'Frozen - IGA SHORT RIBS POTONG 5-7cm', 'unit' => 'KG'],
            ['kode' => 'FF020', 'nama' => 'Frozen - NUGGET AYAM "FIESTA" (500gr)', 'unit' => 'PACK'],
            ['kode' => 'FF021', 'nama' => 'Frozen - SOSIS AYAM "BAVAR\'I" (1kg)', 'unit' => 'PACK'],
            ['kode' => 'FF022', 'nama' => 'Frozen - SOSIS AYAM "INDOGUNA" (1kg)', 'unit' => 'PACK'],
            ['kode' => 'FF023', 'nama' => 'Frozen - SOSIS SAPI "INDOGUNA" (1kg)', 'unit' => 'PACK'],
            ['kode' => 'FF024', 'nama' => 'Frozen - AYAM UTUH (......?? /kg) POTONG', 'unit' => 'KG'],
        ];

        foreach ($items as $item) {
            KatalogBarang::updateOrCreate(
                ['kode_barang' => $item['kode']],
                [
                    'nama_barang' => $item['nama'],
                    'category_id' => $category->id,
                    'unit_id' => $units[$item['unit']]->id,
                ]
            );
        }

        $this->command->info('✓ ' . count($items) . ' frozen food items inserted/updated');
    }
}

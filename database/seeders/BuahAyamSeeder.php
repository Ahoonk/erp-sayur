<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\KatalogBarang;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class BuahAyamSeeder extends Seeder
{
    public function run(): void
    {
        $buah = Category::where('nama', 'Buah')->first();
        $ayam = Category::where('nama', 'Ayam Daging dan Olahan')->first();

        if (!$buah || !$ayam) {
            $this->command->error('Category "Buah" and/or "Ayam Daging dan Olahan" not found. Run MasterDataSeeder first.');
            return;
        }

        $unitNames = ['KG', 'PCS', 'PACK', 'PKT', 'EKOR', 'SSR', 'TDN', 'BTL', 'CTN', 'GLN', 'KLG', 'KRG', 'SLOP', 'BAL'];
        foreach ($unitNames as $unitName) {
            Unit::firstOrCreate(['nama' => $unitName]);
        }
        $units = Unit::whereIn('nama', $unitNames)->get()->keyBy('nama');

        $buahItems = [
            ['kode' => 'F001', 'nama' => 'BUAH ALPUKAT', 'unit' => 'KG'],
            ['kode' => 'F002', 'nama' => 'BUAH ANGGUR HIJAU', 'unit' => 'KG'],
            ['kode' => 'F003', 'nama' => 'BUAH ANGGUR MERAH', 'unit' => 'KG'],
            ['kode' => 'F004', 'nama' => 'BUAH APEL FUJI @7-8pcs', 'unit' => 'KG'],
            ['kode' => 'F005', 'nama' => 'BUAH APEL MALANG @8-10pcs', 'unit' => 'KG'],
            ['kode' => 'F006', 'nama' => 'BUAH APEL MERAH @7-9pcs', 'unit' => 'KG'],
            ['kode' => 'F007', 'nama' => 'BUAH BEET', 'unit' => 'KG'],
            ['kode' => 'F008', 'nama' => 'BUAH BENGKUANG', 'unit' => 'KG'],
            ['kode' => 'F009', 'nama' => 'BUAH BELIMBING', 'unit' => 'KG'],
            ['kode' => 'F010', 'nama' => 'BUAH JAMBU AIR', 'unit' => 'KG'],
            ['kode' => 'F011', 'nama' => 'BUAH JAMBU BIJI MERAH', 'unit' => 'KG'],
            ['kode' => 'F012', 'nama' => 'BUAH JERUK LEMON IMPORT', 'unit' => 'KG'],
            ['kode' => 'F013', 'nama' => 'BUAH JERUK LEMON LOKAL', 'unit' => 'KG'],
            ['kode' => 'F014', 'nama' => 'BUAH JERUK MANDARIN @6-9pcs', 'unit' => 'KG'],
            ['kode' => 'F015', 'nama' => 'BUAH JERUK MEDAN @10-12pcs', 'unit' => 'KG'],
            ['kode' => 'F016', 'nama' => 'BUAH JERUK MEDAN @13-15pcs', 'unit' => 'KG'],
            ['kode' => 'F017', 'nama' => 'BUAH JERUK MEDAN @7-9pcs', 'unit' => 'KG'],
            ['kode' => 'F018', 'nama' => 'BUAH JERUK PONTIANAK @10-12pcs', 'unit' => 'KG'],
            ['kode' => 'F019', 'nama' => 'BUAH JERUK PONTIANAK @13-15pcs', 'unit' => 'KG'],
            ['kode' => 'F020', 'nama' => 'BUAH JERUK PONTIANAK @7-9pcs', 'unit' => 'KG'],
            ['kode' => 'F021', 'nama' => 'BUAH JERUK SUNKIST @4-6pcs', 'unit' => 'KG'],
            ['kode' => 'F022', 'nama' => 'BUAH KEDONDONG', 'unit' => 'KG'],
            ['kode' => 'F023', 'nama' => 'BUAH KELAPA MUDA KEROK (SUPER)', 'unit' => 'PCS'],
            ['kode' => 'F024', 'nama' => 'BUAH KELAPA MUDA UTUH (SUPER)', 'unit' => 'PCS'],
            ['kode' => 'F025', 'nama' => 'BUAH KELENGKENG', 'unit' => 'KG'],
            ['kode' => 'F026', 'nama' => 'BUAH KIWI', 'unit' => 'KG'],
            ['kode' => 'F027', 'nama' => 'BUAH LABU PARANG', 'unit' => 'KG'],
            ['kode' => 'F028', 'nama' => 'BUAH MANGGA HARUM MANIS', 'unit' => 'KG'],
            ['kode' => 'F029', 'nama' => 'BUAH MANGGA MUDA', 'unit' => 'KG'],
            ['kode' => 'F030', 'nama' => 'BUAH MANGGIS', 'unit' => 'KG'],
            ['kode' => 'F031', 'nama' => 'BUAH MELON', 'unit' => 'KG'],
            ['kode' => 'F032', 'nama' => 'BUAH MELON ROCK/OREN', 'unit' => 'KG'],
            ['kode' => 'F033', 'nama' => 'BUAH NAGA MERAH', 'unit' => 'KG'],
            ['kode' => 'F034', 'nama' => 'BUAH NANAS MADU', 'unit' => 'KG'],
            ['kode' => 'F035', 'nama' => 'BUAH NANAS PALEMBANG', 'unit' => 'KG'],
            ['kode' => 'F036', 'nama' => 'BUAH NANAS PALEMBANG', 'unit' => 'PCS'],
            ['kode' => 'F037', 'nama' => 'BUAH PEAR PACKHAM/HIJAU @4-6pcs', 'unit' => 'KG'],
            ['kode' => 'F038', 'nama' => 'BUAH PEAR YALLY @5-7pcs', 'unit' => 'KG'],
            ['kode' => 'F039', 'nama' => 'BUAH PEPAYA CALIFORNIA', 'unit' => 'KG'],
            ['kode' => 'F040', 'nama' => 'BUAH PISANG AMBON UK.BESAR', 'unit' => 'SSR'],
            ['kode' => 'F041', 'nama' => 'BUAH PISANG AMBON UK.SEDANG', 'unit' => 'SSR'],
            ['kode' => 'F042', 'nama' => 'BUAH PISANG KEPOK', 'unit' => 'SSR'],
            ['kode' => 'F043', 'nama' => 'BUAH PISANG KETAN', 'unit' => 'SSR'],
            ['kode' => 'F044', 'nama' => 'BUAH PISANG MULI', 'unit' => 'SSR'],
            ['kode' => 'F045', 'nama' => 'BUAH PISANG MULI', 'unit' => 'TDN'],
            ['kode' => 'F046', 'nama' => 'BUAH PISANG NANGKA', 'unit' => 'SSR'],
            ['kode' => 'F047', 'nama' => 'BUAH PISANG SUNFRESH @7-9pcs', 'unit' => 'KG'],
            ['kode' => 'F048', 'nama' => 'BUAH PISANG TANDUK', 'unit' => 'PCS'],
            ['kode' => 'F049', 'nama' => 'BUAH PISANG TANDUK', 'unit' => 'SSR'],
            ['kode' => 'F050', 'nama' => 'BUAH SALAK PONDOH @10-12pcs', 'unit' => 'KG'],
            ['kode' => 'F051', 'nama' => 'BUAH SALAK PONDOH @13-15pcs', 'unit' => 'KG'],
            ['kode' => 'F052', 'nama' => 'BUAH SALAK PONDOH @16-18pcs', 'unit' => 'KG'],
            ['kode' => 'F053', 'nama' => 'BUAH SEMANGKA KUNING', 'unit' => 'KG'],
            ['kode' => 'F054', 'nama' => 'BUAH SEMANGKA MERAH', 'unit' => 'KG'],
            ['kode' => 'F055', 'nama' => 'BUAH SIRSAK', 'unit' => 'KG'],
            ['kode' => 'F056', 'nama' => 'BUAH STRAWBERRY', 'unit' => 'KG'],
            ['kode' => 'F057', 'nama' => 'BUAH STRAWBERRY', 'unit' => 'PACK'],
            ['kode' => 'F058', 'nama' => 'BUAH DUKU PALEMBANG', 'unit' => 'KG'],
            ['kode' => 'F059', 'nama' => 'BUAH RAMBUTAN', 'unit' => 'KG'],
            ['kode' => 'F060', 'nama' => 'Frozen - BUAH SIRSAK', 'unit' => 'KG'],
            ['kode' => 'F061', 'nama' => 'BUAH JAMBU KRISTAL', 'unit' => 'KG'],
            ['kode' => 'F062', 'nama' => 'BUAH SAWO', 'unit' => 'KG'],
            ['kode' => 'F063', 'nama' => 'BUAH JERUK SANTANG KUNING', 'unit' => 'KG'],
            ['kode' => 'F064', 'nama' => 'BUAH JERUK SANTANG (MADU)', 'unit' => 'KG'],
            ['kode' => 'F065', 'nama' => 'BUAH DURIAN FROZEN', 'unit' => 'KG'],
            ['kode' => 'F066', 'nama' => 'BUAH PISANG SUNFRESH (Fullhand) @7-8p', 'unit' => 'KG'],
            ['kode' => 'F067', 'nama' => 'BUAH KURMA "GOLDEN VALLEY"', 'unit' => 'KG'],
            ['kode' => 'F068', 'nama' => 'BUAH KURMA "SUKARI"', 'unit' => 'KG'],
            ['kode' => 'F069', 'nama' => 'BUAH KURMA "PALM FRUIT" @500gr', 'unit' => 'PACK'],
            ['kode' => 'F070', 'nama' => 'BUAH KURMA "TUNISIA MADU"', 'unit' => 'KG'],
        ];

        $ayamItems = [
            ['kode' => 'A001', 'nama' => 'Frozen - AYAM CEKER', 'unit' => 'KG'],
            ['kode' => 'A002', 'nama' => 'Fresh - AYAM (HATI AMPELA USUS)', 'unit' => 'PKT'],
            ['kode' => 'A003', 'nama' => 'Fresh - AYAM (HATI AMPELA USUS)', 'unit' => 'KG'],
            ['kode' => 'A004', 'nama' => 'Frozen - AYAM PAHA PENTUNG', 'unit' => 'KG'],
            ['kode' => 'A005', 'nama' => 'Frozen - AYAM SAYAP', 'unit' => 'KG'],
            ['kode' => 'A006', 'nama' => 'Frozen - AYAM FILLET DADA TP.KULIT', 'unit' => 'KG'],
            ['kode' => 'A007', 'nama' => 'Frozen - AYAM FILLET PAHA TP. KULIT', 'unit' => 'KG'],
            ['kode' => 'A008', 'nama' => 'Frozen - AYAM GILING DADA', 'unit' => 'KG'],
            ['kode' => 'A009', 'nama' => 'Frozen - AYAM KAMPUNG @800-900gr', 'unit' => 'EKOR'],
            ['kode' => 'A010', 'nama' => 'Frozen - AYAM UTUH @0,7 - 0,8 kg', 'unit' => 'EKOR'],
            ['kode' => 'A011', 'nama' => 'Frozen - AYAM UTUH @0,9 - 1 kg', 'unit' => 'EKOR'],
            ['kode' => 'A012', 'nama' => 'Frozen - AYAM UTUH @1,1 - 1,2 kg', 'unit' => 'KG'],
            ['kode' => 'A013', 'nama' => 'Frozen - AYAM UTUH @1,2 - 1,3 kg', 'unit' => 'KG'],
            ['kode' => 'A014', 'nama' => 'Frozen - BEBEK PECKING @1kg', 'unit' => 'EKOR'],
            ['kode' => 'A015', 'nama' => 'Frozen - BEBEK PECKING @2kg (up)', 'unit' => 'EKOR'],
            ['kode' => 'A016', 'nama' => 'Frozen - BUNTUT SAPI IMPORT (campur)', 'unit' => 'KG'],
            ['kode' => 'A017', 'nama' => 'Frozen - BUNTUT SAPI "CENTRE CUT" D: 6', 'unit' => 'KG'],
            ['kode' => 'A018', 'nama' => 'Frozen - AYAM UTUH @0,5 - 0,6 kg', 'unit' => 'KG'],
            ['kode' => 'A019', 'nama' => 'Frozen - CHICKEN NUGGET "DINO" @500g', 'unit' => 'PACK'],
            ['kode' => 'A020', 'nama' => 'Frozen - CUMI (TUBE) GLZ: 30-40%', 'unit' => 'KG'],
            ['kode' => 'A021', 'nama' => 'Fresh - DAGING DABAT SAPI', 'unit' => 'KG'],
            ['kode' => 'A022', 'nama' => 'Frozen - DAGING BLADE', 'unit' => 'KG'],
            ['kode' => 'A023', 'nama' => 'Frozen - DAGING GILLING', 'unit' => 'KG'],
            ['kode' => 'A024', 'nama' => 'Frozen - DAGING TENDERLOIN', 'unit' => 'KG'],
            ['kode' => 'A025', 'nama' => 'Frozen - DAGING TOP SIDE @1kg', 'unit' => 'KG'],
            ['kode' => 'A026', 'nama' => 'Frozen - DAGING TOP SIDE @5kg/up', 'unit' => 'KG'],
            ['kode' => 'A027', 'nama' => 'Frozen - DAGING HATI @1kg', 'unit' => 'KG'],
            ['kode' => 'A028', 'nama' => 'Frozen - DAGING JANTUNG @1kg', 'unit' => 'KG'],
            ['kode' => 'A029', 'nama' => 'Frozen - DAGING PARU @1kg', 'unit' => 'KG'],
            ['kode' => 'A030', 'nama' => 'Fresh - DAGING USUS SAPI', 'unit' => 'KG'],
            ['kode' => 'A031', 'nama' => 'Frozen - DAGING KAMBING + TULANG (PA', 'unit' => 'KG'],
            ['kode' => 'A032', 'nama' => 'Frozen - DAGING KAMBING TANPA TULANG', 'unit' => 'KG'],
            ['kode' => 'A033', 'nama' => 'Frozen - DIMSUM @500gr', 'unit' => 'PACK'],
            ['kode' => 'A034', 'nama' => 'Frozen - IGA SHORT RIBS UK. 5-7cm', 'unit' => 'KG'],
            ['kode' => 'A035', 'nama' => 'Frozen - IGA SHORT RIBS UK. 10-12cm', 'unit' => 'KG'],
            ['kode' => 'A036', 'nama' => 'Frozen - IKAN DORI FILLET- GLZ: 40-50%', 'unit' => 'KG'],
            ['kode' => 'A037', 'nama' => 'Frozen - IKAN DORI FILLET- GLZ: 5-10%', 'unit' => 'KG'],
            ['kode' => 'A038', 'nama' => 'Fresh - KIKIL KAKI SAPI', 'unit' => 'KG'],
            ['kode' => 'A039', 'nama' => 'Fresh - KIKIL TUNJANG', 'unit' => 'KG'],
            ['kode' => 'A040', 'nama' => 'Frozen - NUGGET AYAM "FIESTA" @500gr', 'unit' => 'PACK'],
            ['kode' => 'A041', 'nama' => 'Frozen - SOSIS AYAM "BAVAR\'I" @1kg', 'unit' => 'PACK'],
            ['kode' => 'A042', 'nama' => 'Frozen - SOSIS AYAM "INDOGUNA" @1kg', 'unit' => 'PACK'],
            ['kode' => 'A043', 'nama' => 'Frozen - SOSIS AYAM @1kg', 'unit' => 'KG'],
            ['kode' => 'A044', 'nama' => 'Frozen - SOSIS SAPI "INDOGUNA" @1kg', 'unit' => 'KG'],
            ['kode' => 'A045', 'nama' => 'Frozen - SOSIS SAPI @1kg', 'unit' => 'KG'],
            ['kode' => 'A046', 'nama' => 'Frozen - KULIT GYOZA / WOTON SKIN', 'unit' => 'PACK'],
            ['kode' => 'A047', 'nama' => 'Frozen - AYAM FILLET PAHA (ADA KULIT)', 'unit' => 'KG'],
            ['kode' => 'A048', 'nama' => 'Frozen - CHIKUWA @500gr', 'unit' => 'PACK'],
            ['kode' => 'A049', 'nama' => 'Frozen - KACANG EDAMAME @450gr', 'unit' => 'PACK'],
            ['kode' => 'A050', 'nama' => 'Frozen - KULIT AYAM GILING', 'unit' => 'KG'],
            ['kode' => 'A051', 'nama' => 'Frozen - SMOKE BEEF @500gr', 'unit' => 'PACK'],
            ['kode' => 'A052', 'nama' => 'Frozen - FF STRAIGHT CUT "CURAH" @2,5kg', 'unit' => 'PACK'],
            ['kode' => 'A053', 'nama' => 'Frozen - IKAN CUCUT FILLET', 'unit' => 'KG'],
            ['kode' => 'A054', 'nama' => 'Frozen - SOSIS SAPI "MAX" @500gr', 'unit' => 'PACK'],
            ['kode' => 'A055', 'nama' => 'Frozen - UDANG KUPAS @60-80pcs/kg', 'unit' => 'KG'],
            ['kode' => 'A056', 'nama' => 'Frozen - FF CRINKLE CUT "CURAH" @2,5kg', 'unit' => 'PACK'],
            ['kode' => 'A057', 'nama' => 'Frozen - TULANG SUMSUM (PAHA)', 'unit' => 'KG'],
            ['kode' => 'A058', 'nama' => 'Frozen - DAGING PERUT (INDIA)', 'unit' => 'KG'],
            ['kode' => 'A059', 'nama' => 'Frozen - MIX VEGETABLE "CURAH" @1kg', 'unit' => 'PACK'],
            ['kode' => 'A060', 'nama' => 'Fresh - USUS AYAM', 'unit' => 'KG'],
            ['kode' => 'A061', 'nama' => 'Frozen - AMPELA AYAM', 'unit' => 'KG'],
            ['kode' => 'A062', 'nama' => 'Frozen - DAGING TOPSIDE (potong 20pcs/kg)', 'unit' => 'KG'],
            ['kode' => 'A063', 'nama' => 'Frozen - HATI AYAM', 'unit' => 'KG'],
            ['kode' => 'A064', 'nama' => 'Frozen - IKAN KAKAP MERAH FILLET', 'unit' => 'KG'],
            ['kode' => 'A065', 'nama' => 'Frozen - DUMPLING AYAM @500gr', 'unit' => 'PACK'],
            ['kode' => 'A066', 'nama' => 'Frozen - DUMPLING KEJU @500gr', 'unit' => 'PACK'],
            ['kode' => 'A067', 'nama' => 'Frozen - BEEF SLICE @500gr', 'unit' => 'PACK'],
        ];

        foreach ($buahItems as $item) {
            KatalogBarang::updateOrCreate(
                ['kode_barang' => $item['kode']],
                [
                    'nama_barang' => $item['nama'],
                    'category_id' => $buah->id,
                    'unit_id' => $units[$item['unit']]->id,
                ]
            );
        }

        foreach ($ayamItems as $item) {
            KatalogBarang::updateOrCreate(
                ['kode_barang' => $item['kode']],
                [
                    'nama_barang' => $item['nama'],
                    'category_id' => $ayam->id,
                    'unit_id' => $units[$item['unit']]->id,
                ]
            );
        }

        $this->command->info('✓ ' . count($buahItems) . ' buah items inserted/updated');
        $this->command->info('✓ ' . count($ayamItems) . ' ayam items inserted/updated');
    }
}

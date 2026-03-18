<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Grade;
use App\Models\Product;
use App\Models\MasterProduct;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('sale_items')->truncate();
        DB::table('sales_transactions')->truncate();
        DB::table('service_parts')->truncate();
        DB::table('service_orders')->truncate();
        DB::table('purchase_items')->truncate();
        DB::table('purchases')->truncate();
        DB::table('products')->truncate();
        DB::table('master_products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $brands = Brand::all();
        $categories = Category::all();
        $grades = Grade::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        $user = User::where('email', 'admin@admin.com')->first() ?: User::first();

        if ($suppliers->isEmpty() || $brands->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Harap jalankan MasterDataSeeder terlebih dahulu!');
            return;
        }

        $this->command->info('Memulai seeding Master Products...');

        $gadgetConfigs = [
            ['nama' => 'Samsung Galaxy S24 Ultra', 'modal' => 15000000, 'jual' => 18000000, 'cat' => 'Smartphone'],
            ['nama' => 'iPhone 15 Pro Max', 'modal' => 20000000, 'jual' => 24000000, 'cat' => 'Smartphone'],
            ['nama' => 'Xiaomi 14 Pro', 'modal' => 9000000, 'jual' => 11000000, 'cat' => 'Smartphone'],
            ['nama' => 'iPad Pro M2', 'modal' => 12000000, 'jual' => 15000000, 'cat' => 'Tablet'],
            ['nama' => 'MacBook Air M3', 'modal' => 16000000, 'jual' => 19000000, 'cat' => 'Laptop'],
        ];

        $accessoryConfigs = [
            ['nama' => 'Anti Gores Ceramic Matte', 'modal' => 15000, 'jual' => 35000, 'cat' => 'Aksesoris'],
            ['nama' => 'Casing Silikon Bening', 'modal' => 10000, 'jual' => 25000, 'cat' => 'Casing'],
            ['nama' => 'Kabel Data Type-C Fast', 'modal' => 25000, 'jual' => 50000, 'cat' => 'Aksesoris'],
            ['nama' => 'Charger 20W Original', 'modal' => 150000, 'jual' => 250000, 'cat' => 'Charger'],
            ['nama' => 'LCD iPhone 11 (Incell)', 'modal' => 450000, 'jual' => 650000, 'cat' => 'Sparepart'],
            ['nama' => 'Baterai Samsung A50', 'modal' => 120000, 'jual' => 180000, 'cat' => 'Sparepart'],
        ];

        $masterGadgets = [];
        foreach ($gadgetConfigs as $cfg) {
            $masterGadgets[] = MasterProduct::create([
                'nama' => $cfg['nama'],
                'brand_id' => $brands->random()->id,
                'category_id' => $categories->where('nama', $cfg['cat'])->first()?->id ?: $categories->random()->id,
                'unit_id' => $units->where('nama', 'Unit')->first()?->id ?: $units->random()->id,
                'keterangan' => 'Gadget Master Data',
            ]);
        }

        $masterAccessories = [];
        foreach ($accessoryConfigs as $cfg) {
            $masterAccessories[] = MasterProduct::create([
                'nama' => $cfg['nama'],
                'brand_id' => $brands->random()->id,
                'category_id' => $categories->where('nama', $cfg['cat'])->first()?->id ?: $categories->random()->id,
                'unit_id' => $units->where('nama', 'Pcs')->first()?->id ?: $units->random()->id,
                'keterangan' => 'Accessory Master Data',
            ]);
        }

        $this->command->info('Memulai seeding Transaksi Pembelian...');

        for ($i = 0; $i < 10; $i++) {
            DB::transaction(function () use ($i, $masterGadgets, $masterAccessories, $grades, $suppliers, $user, $gadgetConfigs, $accessoryConfigs) {
                $supplier = $suppliers->random();
                $date = now()->subDays(rand(1, 30));

                $purchase = Purchase::create([
                    'no_invoice' => 'PURC-' . $date->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'tanggal' => $date->format('Y-m-d'),
                    'supplier_id' => $supplier->id,
                    'user_id' => $user->id,
                    'total' => 0,
                    'keterangan' => 'Restocking ' . $date->format('d M Y'),
                ]);

                $itemsCount = rand(2, 5);
                $invoiceTotal = 0;

                for ($j = 0; $j < $itemsCount; $j++) {
                    $isGadget = rand(0, 1) === 1;

                    if ($isGadget) {
                        $master = $masterGadgets[array_rand($masterGadgets)];
                        $config = collect($gadgetConfigs)->where('nama', $master->nama)->first();

                        $product = Product::create([
                            'master_product_id' => $master->id,
                            'barcode' => 'BC' . rand(10000000, 99999999),
                            'grade_id' => $grades->random()->id,
                            'imei1' => '35' . rand(1000000000000, 9999999999999),
                            'imei2' => '35' . rand(1000000000000, 9999999999999),
                            'harga_modal' => $config['modal'],
                            'harga_jual' => $config['jual'],
                            'stok' => 1,
                        ]);

                        $purchase->items()->create([
                            'product_id' => $product->id,
                            'qty' => 1,
                            'harga_beli' => $config['modal'],
                            'subtotal' => $config['modal'],
                        ]);
                        $invoiceTotal += $config['modal'];
                    } else {
                        $master = $masterAccessories[array_rand($masterAccessories)];
                        $config = collect($accessoryConfigs)->where('nama', $master->nama)->first();
                        $qty = rand(5, 20);

                        // For accessories, check if a product record already exists for this master (Batch/Pool)
                        // Or just create a new one to simulate different batches
                        $product = Product::create([
                            'master_product_id' => $master->id,
                            'barcode' => 'ACC' . rand(100000, 999999),
                            'harga_modal' => $config['modal'],
                            'harga_jual' => $config['jual'],
                            'stok' => $qty,
                        ]);

                        $purchase->items()->create([
                            'product_id' => $product->id,
                            'qty' => $qty,
                            'harga_beli' => $config['modal'],
                            'subtotal' => $config['modal'] * $qty,
                        ]);
                        $invoiceTotal += ($config['modal'] * $qty);
                    }
                }

                $purchase->update(['total' => $invoiceTotal]);
            });
        }

        $this->command->info('✓ Seeding completed successfully.');
    }
}

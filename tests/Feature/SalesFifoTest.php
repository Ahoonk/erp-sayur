<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Grade;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesFifoTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_serialized_sale_uses_fifo_across_batches(): void
    {
        $this->actingAsUserWithPermissions(['create sales']);

        $brand = Brand::create(['nama' => 'Generic']);
        $category = Category::create(['nama' => 'Sparepart']);
        $grade = Grade::create(['nama' => 'Standar']);
        $unit = \App\Models\Unit::create(['nama' => 'Pcs']);

        $master = MasterProduct::create([
            'nama' => 'Kabel Data',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'grade_id' => $grade->id,
            'unit_id' => $unit->id,
            'identifier_type' => 'none',
        ]);

        $lotOld = Product::create([
            'master_product_id' => $master->id,
            'grade_id' => $grade->id,
            'barcode' => 'LOT-OLD-001',
            'harga_modal' => 10000,
            'harga_jual' => 15000,
            'stok' => 2,
        ]);
        DB::table('products')->where('id', $lotOld->id)->update([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $lotNew = Product::create([
            'master_product_id' => $master->id,
            'grade_id' => $grade->id,
            'barcode' => 'LOT-NEW-001',
            'harga_modal' => 12000,
            'harga_jual' => 15000,
            'stok' => 5,
        ]);

        $response = $this->postJson('/api/sales', [
            'pelanggan' => 'Walk-in',
            'metode_pembayaran' => 'cash',
            'jumlah_bayar' => 50000,
            'items' => [
                [
                    // Intentionally choose newer lot; backend should still consume FIFO from older lot first.
                    'product_id' => $lotNew->id,
                    'qty' => 3,
                    'harga_satuan' => 15000,
                ],
            ],
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $lotOld->refresh();
        $lotNew->refresh();

        $this->assertSame(0, $lotOld->stok);
        $this->assertSame(4, $lotNew->stok);

        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('sale_item_allocations', 2);

        $saleItem = \App\Models\SaleItem::first();
        $this->assertSame(32000.0, (float) $saleItem->hpp_total);

        $this->assertDatabaseHas('sale_item_allocations', [
            'sale_item_id' => $saleItem->id,
            'product_id' => $lotOld->id,
            'qty' => 2,
        ]);

        $this->assertDatabaseHas('sale_item_allocations', [
            'sale_item_id' => $saleItem->id,
            'product_id' => $lotNew->id,
            'qty' => 1,
        ]);
    }

    private function actingAsUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $user->givePermissionTo($permissions);
        Sanctum::actingAs($user);

        return $user;
    }
}

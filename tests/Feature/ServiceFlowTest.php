<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_service_with_parts_and_reduce_stock(): void
    {
        $this->actingAsUserWithPermissions(['create services']);
        $product = $this->createStockProduct(stok: 5, hargaJual: 150000);

        $response = $this->postJson('/api/services', [
            'nama_pelanggan' => 'Budi',
            'no_hp_pelanggan' => '08123456789',
            'merk_hp' => 'Samsung',
            'tipe_hp' => 'A54',
            'kerusakan' => 'LCD blank',
            'biaya_jasa' => 200000,
            'parts' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'harga_satuan' => 150000,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('service_orders', [
            'nama_pelanggan' => 'Budi',
            'status' => 'dikerjakan',
        ]);

        $this->assertDatabaseHas('service_parts', [
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $this->assertEquals(3, $product->fresh()->stok);
    }

    public function test_can_update_service_status_and_completion_date(): void
    {
        $this->actingAsUserWithPermissions(['edit services']);

        $service = ServiceOrder::create([
            'nama_pelanggan' => 'Andi',
            'merk_hp' => 'Xiaomi',
            'tipe_hp' => 'Redmi Note',
            'kerusakan' => 'Bootloop',
            'biaya_jasa' => 100000,
            'status' => 'dikerjakan',
            'tanggal_masuk' => now()->toDateString(),
        ]);

        $doneResponse = $this->patchJson("/api/services/{$service->id}/status", [
            'status' => 'selesai',
            'catatan_teknisi' => 'Sudah ganti IC power',
        ]);

        $doneResponse->assertOk()->assertJsonPath('success', true);

        $service->refresh();
        $this->assertSame('selesai', $service->status);
        $this->assertNotNull($service->tanggal_selesai);
        $this->assertSame('Sudah ganti IC power', $service->catatan_teknisi);

        $cancelResponse = $this->patchJson("/api/services/{$service->id}/status", [
            'status' => 'batal',
            'catatan_teknisi' => null,
        ]);

        $cancelResponse->assertOk()->assertJsonPath('success', true);

        $service->refresh();
        $this->assertSame('batal', $service->status);
        $this->assertNull($service->tanggal_selesai);
        $this->assertNull($service->catatan_teknisi);
    }

    public function test_can_add_part_to_existing_service_and_reduce_stock(): void
    {
        $this->actingAsUserWithPermissions(['create services']);

        $product = $this->createStockProduct(stok: 4, hargaJual: 90000);
        $service = ServiceOrder::create([
            'nama_pelanggan' => 'Siti',
            'merk_hp' => 'OPPO',
            'tipe_hp' => 'Reno',
            'kerusakan' => 'Speaker mati',
            'biaya_jasa' => 80000,
            'status' => 'dikerjakan',
            'tanggal_masuk' => now()->toDateString(),
        ]);

        $response = $this->postJson("/api/services/{$service->id}/parts", [
            'product_id' => $product->id,
            'qty' => 1,
            'harga_satuan' => 90000,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('service_parts', [
            'service_order_id' => $service->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $this->assertEquals(3, $product->fresh()->stok);
    }

    /**
     * Authenticate a user with the given permissions via Sanctum.
     */
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

    /**
     * Build a product record that can be used in service parts flow.
     */
    private function createStockProduct(int $stok, int $hargaJual): Product
    {
        $masterProduct = MasterProduct::create([
            'nama' => 'Part Test ' . uniqid(),
        ]);

        return Product::create([
            'master_product_id' => $masterProduct->id,
            'barcode' => 'TEST-' . strtoupper(uniqid()),
            'harga_modal' => 50000,
            'harga_jual' => $hargaJual,
            'stok' => $stok,
        ]);
    }
}

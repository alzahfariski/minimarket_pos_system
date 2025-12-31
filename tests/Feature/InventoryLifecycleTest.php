<?php

namespace Tests\Feature;

use App\Domains\Product\Models\Product;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_inventory_adjustment()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'INV-001',
            'name' => 'Adjust Item',
            'cost' => 1000,
            'price' => 2000,
        ]); // Stock defaults to 0

        Sanctum::actingAs($admin);

        // Positive Adjustment
        $this->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'qty_change' => 10,
            'reason' => 'Found items',
        ])->assertCreated();

        $this->assertEquals(10, $product->fresh()->stock);

        // Negative Adjustment
        $this->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'qty_change' => -3,
            'reason' => 'Damaged',
        ])->assertCreated();

        $this->assertEquals(7, $product->fresh()->stock);
    }

    public function test_inventory_adjustment_rollback_negative()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'INV-002',
            'name' => 'Negative Test',
            'cost' => 1000,
            'price' => 2000,
        ]); // Stock 0

        Sanctum::actingAs($admin);

        $this->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'qty_change' => -5,
            'reason' => 'Impossible',
        ])->assertStatus(422);

        $this->assertEquals(0, $product->fresh()->stock);
    }

    public function test_stock_opname()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'OPN-001',
            'name' => 'Opname Item',
            'cost' => 1000,
            'price' => 2000,
        ]);
        // Set initial stock via adjustment or create + loop
        // Let's assume stock is 0 initially.

        Sanctum::actingAs($admin);

        // Physical count is 50. System is 0. Difference +50.
        $this->postJson('/api/inventory/opname', [
            'product_id' => $product->id,
            'physical_stock' => 50,
            'note' => 'Initial Audit',
        ])->assertCreated();

        $this->assertEquals(50, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_opnames', [
            'product_id' => $product->id,
            'system_stock' => 0,
            'physical_stock' => 50,
            'difference' => 50,
        ]);

        // Physical count is 45. System is 50. Difference -5.
        $this->postJson('/api/inventory/opname', [
            'product_id' => $product->id,
            'physical_stock' => 45,
            'note' => 'Lost items',
        ])->assertCreated();

        $this->assertEquals(45, $product->fresh()->stock);
    }

    public function test_image_upload_minio()
    {
        Storage::fake('s3');
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'IMG-001',
            'name' => 'Image Product',
            'cost' => 1000,
            'price' => 2000,
        ]);

        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson("/api/products/{$product->id}/image", [
            'image' => $file,
        ]);

        $response->assertOk();

        // Check if file was stored in 'products' directory on s3 disk
        // storage path is usually returned.
        $updatedProduct = $product->fresh();
        Storage::disk('s3')->assertExists($updatedProduct->image_path);
    }

    public function test_cache_invalidation()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'CACHE-001',
            'name' => 'Cache Item',
            'cost' => 1000,
            'price' => 2000,
        ]);

        Sanctum::actingAs($admin);

        // Seed Cache
        Cache::put('products:list', ['cached_data']);
        $this->assertTrue(Cache::has('products:list'));

        // Trigger Adjustment -> Should Forget Cache
        $this->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'qty_change' => 1,
            'reason' => 'Cache Test',
        ])->assertCreated();

        // Verify Cache is GONE
        $this->assertFalse(Cache::has('products:list'));
    }
}

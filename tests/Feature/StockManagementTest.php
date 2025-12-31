<?php

namespace Tests\Feature;

use App\Domains\Product\Models\Product;
use App\Domains\Supplier\Models\Supplier;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_increases_stock()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $supplier = Supplier::create(['name' => 'Supplier A']);
        $product = Product::create([
            'sku' => 'PROD-001',
            'name' => 'Test Product',
            'cost' => 1000,
            'price' => 2000,
        ]);
        $product->stock = 10;
        $product->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 50,
                ]
            ]
        ]);

        $response->assertCreated();
        
        // Assert Stock Increased
        $this->assertEquals(60, $product->fresh()->stock);

        // Assert Purchase Created
        $this->assertDatabaseHas('purchases', [
            'total_cost' => 50 * 1000,
            'created_by' => $admin->id,
        ]);

        // Assert Item Created
        $this->assertDatabaseHas('purchase_items', [
            'product_id' => $product->id,
            'quantity' => 50,
            'cost' => 1000,
        ]);
    }

    public function test_rollback_when_invalid_product()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $supplier = Supplier::create(['name' => 'Supplier A']);
        $product = Product::create([
            'sku' => 'PROD-002',
            'name' => 'Safe Product',
            'cost' => 1000,
            'price' => 2000,
        ]);
        $product->stock = 10;
        $product->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                ],
                [
                    'product_id' => 99999, // Invalid
                    'quantity' => 10,
                ]
            ]
        ]);

        $response->assertStatus(422);

        // Assert Rollback: Stock unchanged
        $this->assertEquals(10, $product->fresh()->stock);
        // Assert No Purchase Created
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_quantity_zero_rollback()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $supplier = Supplier::create(['name' => 'Supplier A']);
        $product = Product::create([
            'sku' => 'PROD-003',
            'name' => 'Zero Product',
            'cost' => 1000,
            'price' => 2000,
        ]);
        $product->stock = 10;
        $product->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 0, // Invalid
                ]
            ]
        ]);

        $response->assertStatus(422);
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_cashier_forbidden_purchase()
    {
        $cashier = User::factory()->create(['role' => Role::CASHIER->value]);
        $supplier = Supplier::create(['name' => 'Supplier A']);
        
        Sanctum::actingAs($cashier);

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items' => []
        ]);

        $response->assertForbidden();
    }
}

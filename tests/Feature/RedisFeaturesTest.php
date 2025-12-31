<?php

namespace Tests\Feature;

use App\Domains\Product\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RedisFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_transaction_succeeds_with_lock()
    {
        
        $cashier = User::factory()->create(['role' => 'cashier']);
        $product = Product::create([
            'sku' => 'TEST-LOCK',
            'name' => 'Lock Product',
            'stock' => 10,
            'price' => 1000,
            'cost' => 500
        ]);

        Sanctum::actingAs($cashier);

        $response = $this->postJson('/api/pos', [
            'payment_method' => 'cash',
            'payment_amount' => 10000,
            'items' => [
                ['product_id' => $product->id, 'qty' => 5]
            ]
        ]);

        $response->assertCreated();
        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_product_scan_uses_cache()
    {
        $product = Product::create([
            'sku' => 'SCAN-001',
            'name' => 'Cached Product',
            'stock' => 100,
            'price' => 5000,
            'cost' => 2000
        ]);

        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier);

        // First hit: Should cache
        $response1 = $this->getJson('/api/products/scan/SCAN-001');
        $response1->assertOk()
            ->assertJson(['sku' => 'SCAN-001']);
        
        $this->assertTrue(Cache::has('product:scan:SCAN-001'));
        
    }

    public function test_product_scan_not_found()
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier);

        $response = $this->getJson('/api/products/scan/NON-EXISTENT');
        $response->assertNotFound();
    }
}

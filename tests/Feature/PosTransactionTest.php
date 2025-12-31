<?php

namespace Tests\Feature;

use App\Domains\Product\Models\Product;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_transaction_success()
    {
        $cashier = User::factory()->create(['role' => Role::CASHIER->value]);
        $product = Product::create([
            'sku' => 'POS-001',
            'name' => 'POS Product',
            'cost' => 5000,
            'price' => 10000,
        ]);
        $product->stock = 20;
        $product->save();

        Sanctum::actingAs($cashier);

        $response = $this->postJson('/api/pos', [
            'payment_amount' => 50000,
            'payment_method' => 'CASH',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 3,
                ]
            ]
        ]);

        $response->assertCreated();
        
        // Assert Stock Decrement
        $this->assertEquals(17, $product->fresh()->stock);

        // Assert Transaction Created
        $this->assertDatabaseHas('pos_transactions', [
            'total_amount' => 30000, // 3 * 10000
            'payment_amount' => 50000,
            'payment_method' => 'CASH',
            'change_amount' => 20000,
            'cashier_id' => $cashier->id,
        ]);

        $this->assertDatabaseHas('pos_transactions', [
            'total_amount' => 30000,
            'payment_amount' => 50000,
            'payment_method' => 'CASH',
        ]);

        $transaction = \App\Domains\Pos\Models\PosTransaction::latest()->first();
        $this->assertStringStartsWith('INV-', $transaction->invoice_number);
        $this->assertEquals(22, strlen($transaction->invoice_number));

        // Assert Item Created
        $this->assertDatabaseHas('pos_transaction_items', [
            'product_id' => $product->id,
            'qty' => 3,
            'price_snapshot' => 10000,
            'subtotal' => 30000,
        ]);
    }

    public function test_pos_insufficient_stock_rollback()
    {
        $cashier = User::factory()->create(['role' => Role::CASHIER->value]);
        $product = Product::create([
            'sku' => 'POS-002',
            'name' => 'Scarce Product',
            'cost' => 5000,
            'price' => 10000,
        ]);
        $product->stock = 5;
        $product->save();

        Sanctum::actingAs($cashier);

        $response = $this->postJson('/api/pos', [
            'payment_amount' => 100000,
            'payment_method' => 'CASH',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10, // Requesting more than available
                ]
            ]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // Assert Stock Unchanged (Rollback)
        $this->assertEquals(5, $product->fresh()->stock);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_pos_insufficient_payment_rollback()
    {
        $cashier = User::factory()->create(['role' => Role::CASHIER->value]);
        $product = Product::create([
            'sku' => 'POS-003',
            'name' => 'Expensive Product',
            'cost' => 5000,
            'price' => 20000,
        ]);
        $product->stock = 10;
        $product->save();

        Sanctum::actingAs($cashier);

        $response = $this->postJson('/api/pos', [
            'payment_amount' => 10000, // Less than total (20000)
            'payment_method' => 'CASH',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                ]
            ]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_amount']);

        // Assert Stock Unchanged
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_admin_can_process_pos()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'POS-004',
            'name' => 'Admin Product',
            'cost' => 5000,
            'price' => 10000,
        ]);
        $product->stock = 10;
        $product->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/pos', [
            'payment_amount' => 20000,
            'payment_method' => 'DEBIT',
            'items' => [
                ['product_id' => $product->id, 'qty' => 1]
            ]
        ]);

        $response->assertCreated();
    }

    public function test_unauthorized_access()
    {
        $response = $this->postJson('/api/pos', [
            'payment_amount' => 20000,
            'payment_method' => 'CASH',
            'items' => []
        ]);

        $response->assertUnauthorized();
    }
}

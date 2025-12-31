<?php

namespace Tests\Feature;

use App\Domains\Product\Models\Product;
use App\Domains\Supplier\Models\Supplier;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_and_delete_product()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $product = Product::create([
            'sku' => 'TEST-UPD',
            'name' => 'Original Name',
            'cost' => 1000,
            'price' => 2000,
            'stock' => 0 // Set to 0 to allow deletion test
        ]);

        Sanctum::actingAs($admin);

        // Update
        $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name',
            'price' => 2500
        ])->assertOk();

        $this->assertEquals('Updated Name', $product->fresh()->name);
        $this->assertEquals(2500, $product->fresh()->price);

        // Delete (Soft)
        $this->deleteJson("/api/products/{$product->id}")->assertOk();
        
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cashier_cannot_update_or_delete_product()
    {
        $cashier = User::factory()->create(['role' => Role::CASHIER->value]);
        $product = Product::create([
            'sku' => 'TEST-SEC',
            'name' => 'Secure',
            'cost' => 1000,
            'price' => 2000
        ]);

        Sanctum::actingAs($cashier);

        $this->putJson("/api/products/{$product->id}", ['name' => 'Hacked'])->assertForbidden();
        $this->deleteJson("/api/products/{$product->id}")->assertForbidden();
    }

    public function test_admin_can_manage_suppliers()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        $supplier = Supplier::create(['name' => 'Old Supplier']);

        Sanctum::actingAs($admin);

        // Update
        $this->putJson("/api/suppliers/{$supplier->id}", ['name' => 'New Supplier'])->assertOk();
        $this->assertEquals('New Supplier', $supplier->fresh()->name);

        // Delete
        $this->deleteJson("/api/suppliers/{$supplier->id}")->assertOk();
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_admin_can_manage_cashiers()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN->value]);
        Sanctum::actingAs($admin);

        // Create
        $response = $this->postJson('/api/cashiers', [
            'name' => 'New Cashier',
            'email' => 'cashier@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $cashierId = $response->json('id');
        $this->assertDatabaseHas('users', ['email' => 'cashier@test.com', 'role' => 'cashier']);

        // Update Name Only (Password should remain)
        $this->putJson("/api/cashiers/{$cashierId}", ['name' => 'Updated Cashier'])->assertOk();
        $this->assertDatabaseHas('users', ['id' => $cashierId, 'name' => 'Updated Cashier']);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', \App\Models\User::find($cashierId)->password));

        // Update Password
        $this->putJson("/api/cashiers/{$cashierId}", [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ])->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword', \App\Models\User::find($cashierId)->password));

        // Delete
        $this->deleteJson("/api/cashiers/{$cashierId}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $cashierId]);
    }
}

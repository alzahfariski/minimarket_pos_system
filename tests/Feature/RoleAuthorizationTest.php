<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_route()
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN->value,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/ping')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_cashier_cannot_access_admin_route()
    {
        $cashier = User::factory()->create([
            'role' => Role::CASHIER->value,
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/admin/ping')
            ->assertForbidden();
    }
}

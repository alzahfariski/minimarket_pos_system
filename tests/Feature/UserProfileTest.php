<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_current_user_profile()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'role' => Role::ADMIN,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
                'role' => 'admin', // Enum should be serialized to its backing value
                'email' => $user->email,
            ]);
    }

    public function test_get_current_user_unauthenticated()
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }
}

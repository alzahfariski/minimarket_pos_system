<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_update_profile()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'password',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('new_password', $user->fresh()->password));
    }

    public function test_forgot_password_sends_link()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();
    }



}

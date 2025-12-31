<?php

namespace Tests\Feature;

use App\Domains\Auth\Services\GoogleIdTokenVerifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_google_token_rejected()
    {
        $this->mock(GoogleIdTokenVerifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')
                ->once()
                ->with('invalid_token')
                ->andThrow(ValidationException::withMessages(['id_token' => 'Invalid token']));
        });

        $this->postJson('/api/auth/google', [
            'id_token' => 'invalid_token',
            'device_name' => 'test-device',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['id_token']);
    }

    public function test_google_login_creates_user()
    {
        $payload = [
            'sub' => '1234567890',
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'aud' => config('services.google.client_id'),
        ];

        $this->mock(GoogleIdTokenVerifier::class, function (MockInterface $mock) use ($payload) {
            $mock->shouldReceive('verify')
                ->once()
                ->with('valid_token')
                ->andReturn($payload);
        });

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'valid_token',
            'device_name' => 'test-device',
        ]);

        // Expect 2FA required because new users have 2FA enabled by default
        $response->assertOk()
            ->assertJson([
                'status' => '2fa_required',
            ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'google_sub' => '1234567890',
            'role' => 'cashier',
            'two_factor_enabled' => true,
        ]);
    }

    public function test_google_login_issues_token_for_existing_user_without_2fa()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'google_sub' => '9876543210',
            'two_factor_enabled' => false,
        ]);

        $payload = [
            'sub' => '9876543210',
            'email' => 'existing@example.com',
            'name' => 'Existing User',
            'aud' => config('services.google.client_id'),
        ];

        $this->mock(GoogleIdTokenVerifier::class, function (MockInterface $mock) use ($payload) {
            $mock->shouldReceive('verify')
                ->once()
                ->with('valid_token')
                ->andReturn($payload);
        });

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'valid_token',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_google_login_returns_2fa_required_for_existing_user_with_2fa()
    {
        $user = User::factory()->create([
            'email' => 'secure@example.com',
            'google_sub' => '1122334455',
            'two_factor_enabled' => true,
        ]);

        $payload = [
            'sub' => '1122334455',
            'email' => 'secure@example.com',
            'name' => 'Secure User',
            'aud' => config('services.google.client_id'),
        ];

        $this->mock(GoogleIdTokenVerifier::class, function (MockInterface $mock) use ($payload) {
            $mock->shouldReceive('verify')
                ->once()
                ->with('valid_token')
                ->andReturn($payload);
        });

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'valid_token',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJson(['status' => '2fa_required']);
            
        $this->assertDatabaseHas('user_otps', ['user_id' => $user->id]);
    }

    public function test_google_login_rate_limited()
    {
         $this->mock(GoogleIdTokenVerifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->andReturn([
                'sub' => '123', 
                'email' => 'a@b.c',
                'name' => 'Name',
            ]);
        });

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/google', [
                'id_token' => 'token',
                'device_name' => 'device',
            ])->assertOk();
        }

        $this->postJson('/api/auth/google', [
            'id_token' => 'token',
            'device_name' => 'device',
        ])->assertStatus(429);
    }
}

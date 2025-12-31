<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_otp_success()
    {
        $user = User::factory()->create();
        UserOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'otp' => '123456',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type']);

        $this->assertDatabaseMissing('user_otps', ['user_id' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-device',
        ]);
    }

    public function test_verify_otp_invalid()
    {
        $user = User::factory()->create();
        UserOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'otp' => '654321', // Wrong OTP
            'device_name' => 'test-device',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_otp_expired()
    {
        $user = User::factory()->create();
        UserOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(), // Expired
        ]);

        $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'otp' => '123456',
            'device_name' => 'test-device',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_otp_reused()
    {
        $user = User::factory()->create();
        UserOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        // First attempt success
        $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'otp' => '123456',
            'device_name' => 'test-device',
        ])->assertOk();

        // Second attempt fails (OTP deleted)
        $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'otp' => '123456',
            'device_name' => 'test-device',
        ])->assertStatus(422);
    }

    public function test_verify_otp_rate_limited()
    {
        $user = User::factory()->create();
        
        // No OTP created, to fail validation but consume attempts
        
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/verify-otp', [
                'user_id' => $user->id,
                'otp' => '123456',
                'device_name' => 'test-device',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'otp' => '123456',
            'device_name' => 'test-device',
        ])->assertStatus(429);
    }
}

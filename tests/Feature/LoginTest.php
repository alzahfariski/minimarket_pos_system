<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_2fa_and_does_not_issue_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => '2fa_required',
                'user_id' => $user->id,
            ]);

        $this->assertDatabaseHas('user_otps', [
            'user_id' => $user->id,
        ]);
        
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rate_limited()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertOk();
        }

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(429);
    }
}

<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success()
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Store Owner',
            'email' => 'owner@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJson(['status' => '2fa_required']);

        $user = User::where('email', 'owner@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(Role::ADMIN, $user->role);
        $this->assertTrue($user->two_factor_enabled);
        
        // Ensure OTP sent during register
        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_register_validation()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_triggers_email_otp()
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'owner@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson(['status' => '2fa_required']);

        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_register_rate_limit()
    {
        // Limit is 3
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/register', [
                'name' => 'Spammer',
                'email' => "spam{$i}@test.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertCreated();
        }

        $this->postJson('/api/auth/register', [
            'name' => 'Spammer',
            'email' => 'spam_blocked@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(429);
    }
}

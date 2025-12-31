<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Services\GoogleIdTokenVerifier;
use App\Domains\Auth\Services\OtpService;
use App\Models\User;
use App\Support\Auth\DeviceTokenFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class LoginWithGoogleAction
{
    public function __construct(
        protected GoogleIdTokenVerifier $verifier,
        protected OtpService $otpService,
        protected DeviceTokenFactory $tokenFactory
    ) {}

    public function execute(string $idToken, string $deviceName): array|NewAccessToken
    {
        $payload = $this->verifier->verify($idToken);

        $user = User::withTrashed()->where('google_sub', $payload['sub'])->first();

        // If not found by google_sub, try email but LINK it
        if (! $user) {
            $user = User::withTrashed()->where('email', $payload['email'])->first();
            
            if ($user) {
                $user->update(['google_sub' => $payload['sub']]);
            } else {
                $user = User::create([
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'google_sub' => $payload['sub'],
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'cashier',
                    'two_factor_enabled' => true,
                    'email_verified_at' => now(),
                ]);
            }
        }
        
        if ($user->trashed()) {
             throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => [trans('auth.failed')], // Account disabled
            ]);
        }

        // Policy: If 2FA enabled, trigger OTP
        if ($user->two_factor_enabled) {
            $this->otpService->generateForUser($user);
            
            return [
                'status' => '2fa_required',
                'user_id' => $user->id,
            ];
        }

        // Else issue token directly
        return $this->tokenFactory->make($user, $deviceName);
    }
}

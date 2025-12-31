<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterAction
{
    public function __construct(
        protected \App\Domains\Auth\Services\OtpService $otpService
    ) {}

    public function execute(array $data): array
    {
        // Prevent manual role injection just in case, though Request should handle it.
        $data['role'] = Role::ADMIN->value;
        $data['password'] = Hash::make($data['password']);
        $data['two_factor_enabled'] = true;
        
        $user = User::create($data);

        // Send OTP immediately
        $otp = $this->otpService->generateForUser($user);
        $this->otpService->send($user, $otp);

        return [
            'status' => '2fa_required',
            'user_id' => $user->id,
        ];
    }
}

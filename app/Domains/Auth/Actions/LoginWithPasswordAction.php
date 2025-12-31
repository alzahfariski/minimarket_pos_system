<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Services\OtpService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginWithPasswordAction
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function execute(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $otp = $this->otpService->generateForUser($user);
        $this->otpService->send($user, $otp);

        return [
            'status' => '2fa_required',
            'user_id' => $user->id,
        ];
    }
}

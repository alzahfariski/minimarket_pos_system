<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Services\OtpService;
use App\Models\User;
use App\Support\Auth\DeviceTokenFactory;
use Laravel\Sanctum\NewAccessToken;

class VerifyOtpAction
{
    public function __construct(
        protected OtpService $otpService,
        protected DeviceTokenFactory $tokenFactory
    ) {}

    public function execute(string $userId, string $otp, string $deviceName): NewAccessToken
    {
        $user = User::findOrFail($userId);

        $this->otpService->verify($user, $otp);

        return $this->tokenFactory->make($user, $deviceName);
    }
}

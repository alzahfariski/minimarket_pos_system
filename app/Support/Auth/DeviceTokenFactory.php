<?php

namespace App\Support\Auth;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class DeviceTokenFactory
{
    public function make(User $user, string $deviceName): NewAccessToken
    {
        return $user->createToken($deviceName, ['*']);
    }
}

<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;

class LogoutAction
{
    public function logoutCurrent(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}

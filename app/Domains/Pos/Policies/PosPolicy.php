<?php

namespace App\Domains\Pos\Policies;

use App\Models\User;
use App\Support\Auth\Role;

class PosPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::CASHIER]);
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::CASHIER]);
    }

    public function view(User $user, \App\Domains\Pos\Models\PosTransaction $transaction): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::CASHIER]);
    }
}

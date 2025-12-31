<?php

namespace App\Domains\Product\Policies;

use App\Models\User;
use App\Support\Auth\Role;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->role === Role::ADMIN;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::CASHIER]);
    }

    public function update(User $user): bool
    {
        return $user->role === Role::ADMIN;
    }

    public function delete(User $user): bool
    {
        return $user->role === Role::ADMIN;
    }
}

<?php

namespace App\Domains\Purchase\Policies;

use App\Domains\Purchase\Models\Purchase;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Auth\Access\Response;

class PurchasePolicy
{
    public function create(User $user): bool
    {
        return $user->role === Role::ADMIN;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::CASHIER]);
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::CASHIER]);
    }
}

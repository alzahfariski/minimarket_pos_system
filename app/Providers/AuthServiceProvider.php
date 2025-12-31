<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('admin-only', function (User $user) {
            return $user->role === Role::ADMIN;
        });

        Gate::policy(\App\Domains\Product\Models\Product::class, \App\Domains\Product\Policies\ProductPolicy::class);
        Gate::policy(\App\Domains\Supplier\Models\Supplier::class, \App\Domains\Supplier\Policies\SupplierPolicy::class);
        Gate::policy(\App\Domains\Purchase\Models\Purchase::class, \App\Domains\Purchase\Policies\PurchasePolicy::class);
        Gate::policy(\App\Domains\Pos\Models\PosTransaction::class, \App\Domains\Pos\Policies\PosPolicy::class);
    }
}

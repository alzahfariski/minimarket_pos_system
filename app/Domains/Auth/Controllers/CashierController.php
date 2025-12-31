<?php

namespace App\Domains\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CashierController extends Controller
{
    public function index(): JsonResponse
    {
        if (request()->user()->role !== Role::ADMIN) {
            abort(403);
        }

        $cashiers = User::where('role', Role::CASHIER)->get();
        return response()->json($cashiers);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== Role::ADMIN) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => Role::CASHIER,
            'two_factor_enabled' => true, // Enforcing 2FA for Cashiers too
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $cashier): JsonResponse
    {
        if ($request->user()->role !== Role::ADMIN) {
            abort(403);
        }
        
        // Ensure we are only editing cashiers
        if ($cashier->role !== Role::CASHIER) {
            abort(403, 'Cannot edit non-cashier users via this endpoint.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $cashier->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $cashier->update($validated);

        return response()->json($cashier);
    }

    public function destroy(Request $request, User $cashier): JsonResponse
    {
        if ($request->user()->role !== Role::ADMIN) {
            abort(403);
        }
        
        if ($cashier->role !== Role::CASHIER) {
            abort(403, 'Cannot delete non-cashier users via this endpoint.');
        }

        $cashier->delete();

        return response()->json(['message' => 'Cashier deleted successfully']);
    }
}

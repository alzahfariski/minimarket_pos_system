<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\ChangePasswordRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}

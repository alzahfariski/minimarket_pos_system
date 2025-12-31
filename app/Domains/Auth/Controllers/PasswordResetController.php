<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\ForgotPasswordRequest;
use App\Domains\Auth\Requests\ResetPasswordRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{

    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => __($status)])
            : response()->json(['email' => __($status)], 422);
    }


}

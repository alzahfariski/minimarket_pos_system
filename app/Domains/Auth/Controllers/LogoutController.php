<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        protected LogoutAction $action
    ) {}

    public function logout(Request $request): JsonResponse
    {
        $this->action->logoutCurrent($request->user());

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->action->logoutAll($request->user());

        return response()->json(['message' => 'Logged out from all devices successfully']);
    }
}

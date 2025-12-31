<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\RegisterAction;
use App\Domains\Auth\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use App\Support\RateLimit\AuthRateLimiter;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        protected AuthRateLimiter $limiter // Reuse or extend limiter
    ) {}

    public function __invoke(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        // Rate limit: 3 requests per minute by IP (Strict for registration)
        $this->limiter->checkByIp($request, 3);

        $result = $action->execute($request->validated());

        return response()->json($result, 201);
    }
}

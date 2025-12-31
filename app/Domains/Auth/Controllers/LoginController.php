<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\LoginWithPasswordAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Support\RateLimit\AuthRateLimiter;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        protected AuthRateLimiter $limiter,
        protected LoginWithPasswordAction $action
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $this->limiter->check($request);

        try {
            $result = $this->action->execute(
                $request->input('email'),
                $request->input('password')
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            // Rate limiter is not cleared on failure to prevent brute force
            throw $e;
        }
    }

    public function verifyOtp(
        \App\Domains\Auth\Requests\VerifyOtpRequest $request,
        \App\Domains\Auth\Actions\VerifyOtpAction $action
    ): \App\Domains\Auth\Resources\AuthTokenResource {
        $this->limiter->check($request);

        try {
            $token = $action->execute(
                $request->input('user_id'),
                $request->input('otp'),
                $request->input('device_name')
            );

            $this->limiter->clear($request);

            return new \App\Domains\Auth\Resources\AuthTokenResource($token);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function googleLogin(
        \App\Domains\Auth\Requests\GoogleLoginRequest $request,
        \App\Domains\Auth\Actions\LoginWithGoogleAction $action
    ): JsonResponse|\App\Domains\Auth\Resources\AuthTokenResource {
        
        $this->limiter->check($request);

        try {
            $result = $action->execute(
                $request->input('id_token'),
                $request->input('device_name')
            );
            
            if ($result instanceof \Laravel\Sanctum\NewAccessToken) {
                return new \App\Domains\Auth\Resources\AuthTokenResource($result);
            }

            return response()->json($result); // 2fa_required
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

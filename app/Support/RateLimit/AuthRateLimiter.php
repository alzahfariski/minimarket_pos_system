<?php

namespace App\Support\RateLimit;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthRateLimiter
{
    protected const MAX_ATTEMPTS = 5;
    protected const DECAY_MINUTES = 1;

    public function __construct(
        protected RateLimiter $limiter
    ) {}

    public function check(Request $request): void
    {
        $this->checkWithLimit($request, self::MAX_ATTEMPTS);
    }

    public function checkWithLimit(Request $request, int $maxAttempts): void
    {
        $key = $this->throttleKey($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            abort(429, trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]));
        }

        $this->limiter->hit($key, self::DECAY_MINUTES * 60);
    }

    // Expose underlying limiter methods safely if needed, or just use checkWithLimit
    public function tooManyAttempts(Request $request, int $maxAttempts): bool
    {
         return $this->limiter->tooManyAttempts($this->throttleKey($request), $maxAttempts);
    }

    public function increment(Request $request): void
    {
        $this->limiter->hit($this->throttleKey($request), self::DECAY_MINUTES * 60);
    }

    public function checkByIp(Request $request, int $maxAttempts): void
    {
        $key = $request->ip(); // Simple IP key for registration spam

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            abort(429, trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]));
        }

        $this->limiter->hit($key, self::DECAY_MINUTES * 60);
    }


    public function clear(Request $request): void
    {
        $this->limiter->clear($this->throttleKey($request));
    }

    protected function throttleKey(Request $request): string
    {
        if ($request->has('user_id')) {
            return Str::transliterate($request->input('user_id').'|'.$request->ip());
        }
        
        // Use Email OR IP for registration if email missing (though validation catches it)
        $identifier = $request->input('email') ?: $request->ip();

        return Str::transliterate(Str::lower($identifier).'|'.$request->ip());
    }
}

<?php

namespace App\Domains\Auth\Services;

use Google\Client as GoogleClient;
use Illuminate\Validation\ValidationException;

class GoogleIdTokenVerifier
{
    public function __construct(
        protected GoogleClient $client
    ) {}

    public function verify(string $idToken): array
    {
        try {
            $payload = $this->client->verifyIdToken($idToken);
        } catch (\Exception $e) {
            $payload = false;
        }

        if (! $payload) {
            throw ValidationException::withMessages([
                'id_token' => [trans('auth.failed')],
            ]);
        }

        // Audience check is handled by verifyIdToken if client ID is set,
        // but explicit check adds safety if client is reused.
        if ($payload['aud'] !== config('services.google.client_id')) {
             throw ValidationException::withMessages([
                'id_token' => [trans('auth.failed')],
            ]);
        }

        return $payload;
    }
}

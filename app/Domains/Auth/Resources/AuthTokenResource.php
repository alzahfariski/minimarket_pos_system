<?php

namespace App\Domains\Auth\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $this->plainTextToken,
        ];
    }
}

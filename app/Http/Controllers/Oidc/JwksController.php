<?php

namespace App\Http\Controllers\Oidc;

use App\Oidc\SigningKey;
use Illuminate\Http\JsonResponse;

/**
 * Publishes the public half of the signing key so relying parties can verify
 * an ID token without sharing a secret with us.
 */
class JwksController
{
    public function __invoke(SigningKey $key): JsonResponse
    {
        return response()
            ->json(['keys' => [$key->jsonWebKey()]])
            ->header('Cache-Control', 'public, max-age=3600');
    }
}

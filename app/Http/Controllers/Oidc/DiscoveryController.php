<?php

namespace App\Http\Controllers\Oidc;

use App\Oidc\IdTokenBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Publishes the OpenID Provider metadata a relying party reads to configure
 * itself. Everything here is derived from the live application, so it can
 * never drift from what the endpoints actually do.
 */
class DiscoveryController
{
    public function __invoke(IdTokenBuilder $idTokens): JsonResponse
    {
        return response()->json([
            'issuer' => $idTokens->issuer(),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'userinfo_endpoint' => route('oidc.userinfo'),
            'jwks_uri' => route('oidc.jwks'),
            'end_session_endpoint' => route('oidc.logout'),
            'scopes_supported' => ['openid', 'profile', 'email'],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post', 'none'],
            'code_challenge_methods_supported' => ['S256'],
            'claims_supported' => [
                'iss', 'aud', 'sub', 'iat', 'exp', 'auth_time', 'nonce', 'at_hash',
                'name', 'updated_at', 'email', 'email_verified',
            ],
        ]);
    }
}

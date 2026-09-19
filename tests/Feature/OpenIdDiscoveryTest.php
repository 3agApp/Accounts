<?php

use App\Oidc\SigningKey;

it('publishes a discovery document pointing at the real endpoints', function () {
    $this->get('/.well-known/openid-configuration')
        ->assertOk()
        ->assertJson([
            'issuer' => config('app.url'),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'userinfo_endpoint' => route('oidc.userinfo'),
            'jwks_uri' => route('oidc.jwks'),
            'end_session_endpoint' => route('oidc.logout'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'code_challenge_methods_supported' => ['S256'],
        ])
        ->assertJsonPath('scopes_supported', ['openid', 'profile', 'email']);
});

it('publishes the signing key as a JWK', function () {
    $response = $this->get('/oauth/jwks')->assertOk();

    $key = $response->json('keys.0');

    expect($key)
        ->toHaveKeys(['kty', 'use', 'alg', 'kid', 'n', 'e'])
        ->and($key['kty'])->toBe('RSA')
        ->and($key['alg'])->toBe('RS256')
        ->and($key['kid'])->toBe(app(SigningKey::class)->keyId());
});

it('does not expose the private key through the JWKS', function () {
    $response = $this->get('/oauth/jwks')->assertOk();

    expect($response->json('keys.0'))->not->toHaveKey('d');
});

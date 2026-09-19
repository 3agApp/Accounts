<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = firstPartyClient();
    $this->user->clients()->attach($this->client, ['granted_at' => now()]);

    [$verifier, $challenge] = pkcePair();

    $this->actingAs($this->user);

    $response = $this->get('/oauth/authorize?'.http_build_query([
        'client_id' => $this->client->id,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'response_type' => 'code',
        'scope' => 'openid email',
        'state' => 'state-value',
        'nonce' => 'nonce-value',
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]));

    $this->tokens = $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'code_verifier' => $verifier,
        'code' => authorizationCodeFrom($response),
    ])->assertOk()->json();
});

it('re-issues an id token through the refresh grant', function () {
    $refreshed = $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'refresh_token' => $this->tokens['refresh_token'],
        'scope' => 'openid email',
    ])->assertOk()->json();

    $claims = decodeJwtPayload($refreshed['id_token']);

    expect($claims['sub'])->toBe($this->user->public_id)
        ->and($claims['aud'])->toBe($this->client->id)
        ->and($claims['email'])->toBe($this->user->email);
});

it('does not reuse the original nonce on a refreshed id token', function () {
    expect(decodeJwtPayload($this->tokens['id_token'])['nonce'])->toBe('nonce-value');

    $refreshed = $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'refresh_token' => $this->tokens['refresh_token'],
        'scope' => 'openid',
    ])->assertOk()->json();

    expect(decodeJwtPayload($refreshed['id_token']))->not->toHaveKey('nonce');
});

it('stops refreshing once the product is revoked', function () {
    $this->artisan('accounts:revoke', [
        'email' => $this->user->email,
        'client' => 'SalesReport',
    ])->assertSuccessful();

    $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'refresh_token' => $this->tokens['refresh_token'],
        'scope' => 'openid',
    ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
});

it('rejects a refresh token presented by another client', function () {
    $other = firstPartyClient('ProductSyncManager', 'http://localhost:8002/auth/accounts/callback');

    $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $other->id,
        'client_secret' => $other->plainSecret,
        'refresh_token' => $this->tokens['refresh_token'],
        'scope' => 'openid',
    ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
});

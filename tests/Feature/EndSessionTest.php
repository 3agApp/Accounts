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
        'scope' => 'openid',
        'state' => 'state-value',
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]));

    $this->idToken = $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'code_verifier' => $verifier,
        'code' => authorizationCodeFrom($response),
    ])->assertOk()->json('id_token');
});

it('signs the user out', function () {
    $this->get('/oauth/logout')->assertRedirect(route('dashboard'));

    $this->assertGuest();
});

it('returns to a redirect uri the client registered', function () {
    $this->get('/oauth/logout?'.http_build_query([
        'id_token_hint' => $this->idToken,
        'post_logout_redirect_uri' => 'http://localhost:8001/',
    ]))->assertRedirect('http://localhost:8001/');
});

it('passes the state back to the client', function () {
    $this->get('/oauth/logout?'.http_build_query([
        'id_token_hint' => $this->idToken,
        'post_logout_redirect_uri' => 'http://localhost:8001/',
        'state' => 'round-trip',
    ]))->assertRedirect('http://localhost:8001/?state=round-trip');
});

it('refuses a redirect uri the client did not register', function () {
    $this->get('/oauth/logout?'.http_build_query([
        'id_token_hint' => $this->idToken,
        'post_logout_redirect_uri' => 'https://evil.test/steal',
    ]))->assertRedirect(route('dashboard'));
});

it('refuses a redirect uri with no proof of which client is asking', function () {
    $this->get('/oauth/logout?'.http_build_query([
        'post_logout_redirect_uri' => 'http://localhost:8001/',
    ]))->assertRedirect(route('dashboard'));
});

it('refuses a forged id token hint', function () {
    [$header, $payload] = explode('.', $this->idToken);

    $this->get('/oauth/logout?'.http_build_query([
        'id_token_hint' => $header.'.'.$payload.'.'.base64_encode('not-a-signature'),
        'post_logout_redirect_uri' => 'http://localhost:8001/',
    ]))->assertRedirect(route('dashboard'));
});

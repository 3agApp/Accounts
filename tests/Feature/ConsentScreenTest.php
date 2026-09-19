<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = User::factory()->create();
    [$this->verifier, $this->challenge] = pkcePair();
});

/**
 * Start an authorization request for the given client.
 */
function authorizeClient(Client $client, string $redirectUri, array $overrides = []): TestResponse
{
    return test()->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => 'state-value',
        'code_challenge' => test()->challenge,
        'code_challenge_method' => 'S256',
        ...$overrides,
    ]));
}

it('asks a third-party client for consent', function () {
    $client = thirdPartyClient();
    $this->user->clients()->attach($client, ['granted_at' => now()]);

    $response = $this->actingAs($this->user)
        ->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => 'https://example.test/callback',
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => 'state-value',
            'code_challenge' => $this->challenge,
            'code_challenge_method' => 'S256',
        ]));

    $response->assertOk()
        ->assertViewIs('oauth.authorize')
        ->assertSee('Some Other App')
        ->assertSee('Read your email address');
});

it('issues no code until a third-party client is approved', function () {
    $client = thirdPartyClient();
    $this->user->clients()->attach($client, ['granted_at' => now()]);

    $this->actingAs($this->user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'https://example.test/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'state' => 'state-value',
        'code_challenge' => $this->challenge,
        'code_challenge_method' => 'S256',
    ]))->assertOk();

    expect(Passport::authCode()->newQuery()->count())->toBe(0);
});

it('issues a code once a third-party client is approved', function () {
    $client = thirdPartyClient();
    $this->user->clients()->attach($client, ['granted_at' => now()]);

    $this->actingAs($this->user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'https://example.test/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'state' => 'state-value',
        'nonce' => 'nonce-value',
        'code_challenge' => $this->challenge,
        'code_challenge_method' => 'S256',
    ]))->assertOk();

    $response = $this->post('/oauth/authorize', [
        'auth_token' => session('authToken'),
    ]);

    $response->assertRedirectContains('https://example.test/callback');

    expect(authorizationCodeFrom($response))->not->toBeEmpty();
});

it('keeps the nonce across the consent screen', function () {
    $client = thirdPartyClient();
    $this->user->clients()->attach($client, ['granted_at' => now()]);

    $this->actingAs($this->user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'https://example.test/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'state' => 'state-value',
        'nonce' => 'survives-the-post',
        'code_challenge' => $this->challenge,
        'code_challenge_method' => 'S256',
    ]))->assertOk();

    $this->post('/oauth/authorize', ['auth_token' => session('authToken')])->assertRedirect();

    expect(Passport::authCode()->newQuery()->first()->nonce)->toBe('survives-the-post');
});

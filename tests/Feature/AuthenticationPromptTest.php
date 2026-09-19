<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = firstPartyClient();
    $this->user->clients()->attach($this->client, ['granted_at' => now()]);

    [, $this->challenge] = pkcePair();
});

/**
 * Start an authorization request with the given extra parameters.
 */
function promptAuthorize(array $overrides = []): TestResponse
{
    return test()->get('/oauth/authorize?'.http_build_query([
        'client_id' => test()->client->id,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'state' => 'state-value',
        'code_challenge' => test()->challenge,
        'code_challenge_method' => 'S256',
        ...$overrides,
    ]));
}

it('answers prompt=none with login_required rather than a login page', function () {
    $response = promptAuthorize(['prompt' => 'none']);

    $response->assertRedirectContains('http://localhost:8001/auth/accounts/callback');
    $response->assertRedirectContains('error=login_required');
});

it('answers prompt=none for a signed-in user with a code', function () {
    $this->actingAs($this->user);

    $response = promptAuthorize(['prompt' => 'none']);

    expect(authorizationCodeFrom($response))->not->toBeEmpty();
});

it('forces a fresh login when the session is older than max_age', function () {
    $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->travel(10)->minutes();

    promptAuthorize(['max_age' => 60])->assertRedirect(route('login'));

    $this->assertGuest();
});

it('accepts a session that is still within max_age', function () {
    $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => 'password',
    ])->assertRedirect();

    $response = promptAuthorize(['max_age' => 3600]);

    expect(authorizationCodeFrom($response))->not->toBeEmpty();
});

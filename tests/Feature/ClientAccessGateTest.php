<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = firstPartyClient();
    [, $this->challenge] = pkcePair();
});

/**
 * Ask for an authorization code for the seeded first-party client.
 */
function requestAuthorization(): TestResponse
{
    return test()->get('/oauth/authorize?'.http_build_query([
        'client_id' => test()->client->id,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'response_type' => 'code',
        'scope' => 'openid',
        'state' => 'state-value',
        'code_challenge' => test()->challenge,
        'code_challenge_method' => 'S256',
    ]));
}

it('refuses a user who has not been granted the product', function () {
    $this->actingAs($this->user);

    requestAuthorization()
        ->assertForbidden()
        ->assertViewIs('oauth.access-denied')
        ->assertSee("You don't have access to SalesReport", escape: false);
});

it('issues no authorization code to a user without a grant', function () {
    $this->actingAs($this->user);

    requestAuthorization()->assertForbidden();

    expect(Passport::authCode()->newQuery()->count())->toBe(0);
});

it('lets a granted user straight through', function () {
    $this->user->clients()->attach($this->client, ['granted_at' => now()]);

    $this->actingAs($this->user);

    requestAuthorization()->assertRedirectContains('http://localhost:8001/auth/accounts/callback');
});

it('locks the user out again once the grant is revoked', function () {
    $this->user->clients()->attach($this->client, ['granted_at' => now()]);

    $this->actingAs($this->user);
    requestAuthorization()->assertRedirect();

    $this->artisan('accounts:revoke', [
        'email' => $this->user->email,
        'client' => 'SalesReport',
    ])->assertSuccessful();

    requestAuthorization()->assertForbidden();
});

it('revokes the tokens a user already holds for the product', function () {
    $this->user->clients()->attach($this->client, ['granted_at' => now()]);

    Passport::token()->forceFill([
        'id' => 'token-id',
        'user_id' => $this->user->id,
        'client_id' => $this->client->id,
        'scopes' => ['openid'],
        'revoked' => false,
        'expires_at' => now()->addHour(),
    ])->save();

    $this->artisan('accounts:revoke', [
        'email' => $this->user->email,
        'client' => 'SalesReport',
    ])->assertSuccessful();

    expect(Passport::token()->newQuery()->find('token-id')->revoked)->toBeTrue();
});

it('grants access from the console', function () {
    $this->artisan('accounts:grant', [
        'email' => $this->user->email,
        'client' => 'SalesReport',
    ])->assertSuccessful();

    expect($this->user->fresh()->canAccessClient($this->client))->toBeTrue();
});

it('does not grant access twice', function () {
    foreach (range(1, 2) as $ignored) {
        $this->artisan('accounts:grant', [
            'email' => $this->user->email,
            'client' => 'SalesReport',
        ])->assertSuccessful();
    }

    expect($this->user->clients()->count())->toBe(1);
});

it('reports an unknown account', function () {
    $this->artisan('accounts:grant', [
        'email' => 'nobody@example.com',
        'client' => 'SalesReport',
    ])->assertFailed();
});

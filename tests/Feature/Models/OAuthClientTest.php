<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->redirectUri = 'http://127.0.0.1:8001/auth/accounts/callback';
});

function authorizeUrl(string $clientId, string $redirectUri, array $extra = []): string
{
    return '/oauth/authorize?'.http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => 'state-value',
        ...$extra,
    ]);
}

it('redirects a guest to the login page', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'SalesReport',
        redirectUris: [$this->redirectUri],
    );

    $this->get(authorizeUrl($client->getKey(), $this->redirectUri))
        ->assertRedirect(route('login'));
});

it('issues an authorization code without a consent screen for a first-party client', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'SalesReport',
        redirectUris: [$this->redirectUri],
    );

    $response = $this->actingAs(User::factory()->create())
        ->get(authorizeUrl($client->getKey(), $this->redirectUri));

    $response->assertRedirectContains($this->redirectUri);
    $response->assertRedirectContains('code=');
    $response->assertRedirectContains('state=state-value');
});

it('renders the consent screen for a third-party client', function () {
    $owner = User::factory()->create();

    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'Third Party App',
        redirectUris: [$this->redirectUri],
        user: $owner,
    );

    $response = $this->actingAs(User::factory()->create())
        ->get(authorizeUrl($client->getKey(), $this->redirectUri, ['nonce' => 'nonce-value']));

    $response->assertOk();
    $response->assertViewIs('auth.oauth.authorize');
    $response->assertSee('Third Party App');
    $response->assertSee('Approve');
    $response->assertSee('Deny');
    $response->assertSee(route('passport.authorizations.approve').'?nonce=nonce-value', escape: false);
});

it('issues an authorization code when a third-party consent is approved', function () {
    $owner = User::factory()->create();

    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'Third Party App',
        redirectUris: [$this->redirectUri],
        user: $owner,
    );

    $this->actingAs(User::factory()->create())
        ->get(authorizeUrl($client->getKey(), $this->redirectUri));

    $response = $this->post(route('passport.authorizations.approve'), [
        'auth_token' => session('authToken'),
    ]);

    $response->assertRedirectContains($this->redirectUri);
    $response->assertRedirectContains('code=');
});

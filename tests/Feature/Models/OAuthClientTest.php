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

it('shows a continue-as prompt for a first-party client', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'SalesReport',
        redirectUris: [$this->redirectUri],
    );

    $user = User::factory()->create([
        'name' => 'Jamie Example',
        'email' => 'jamie@example.com',
    ]);

    $response = $this->actingAs($user)
        ->get(authorizeUrl($client->getKey(), $this->redirectUri));

    $response->assertOk();
    $response->assertViewIs('auth.oauth.authorize');
    $response->assertSee('Continue to SalesReport');
    $response->assertSee('Jamie Example');
    $response->assertSee('jamie@example.com');
    $response->assertSee('Continue');
    $response->assertSee('Use a different account');
    $response->assertDontSee('>Deny<', false);
});

it('still shows the continue-as prompt when prompt=consent is sent', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'SalesReport',
        redirectUris: [$this->redirectUri],
    );

    $user = User::factory()->create([
        'name' => 'Jamie Example',
        'email' => 'jamie@example.com',
    ]);

    $this->actingAs($user)
        ->get(authorizeUrl($client->getKey(), $this->redirectUri));

    $this->post(route('passport.authorizations.approve'), [
        'auth_token' => session('authToken'),
    ])->assertRedirectContains($this->redirectUri);

    $response = $this->actingAs($user)
        ->get(authorizeUrl($client->getKey(), $this->redirectUri, ['prompt' => 'consent']));

    $response->assertOk();
    $response->assertViewIs('auth.oauth.authorize');
    $response->assertSee('Continue to SalesReport');
});

it('issues an authorization code when the continue-as prompt is approved', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'SalesReport',
        redirectUris: [$this->redirectUri],
    );

    $this->actingAs(User::factory()->create())
        ->get(authorizeUrl($client->getKey(), $this->redirectUri));

    $response = $this->post(route('passport.authorizations.approve'), [
        'auth_token' => session('authToken'),
    ]);

    $response->assertRedirectContains($this->redirectUri);
    $response->assertRedirectContains('code=');
    $response->assertRedirectContains('state=state-value');
});

it('signs out and returns to authorize when switching accounts', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'SalesReport',
        redirectUris: [$this->redirectUri],
    );

    $authorize = authorizeUrl($client->getKey(), $this->redirectUri, ['prompt' => 'consent']);
    $user = User::factory()->create();

    $this->actingAs($user)->get($authorize)->assertOk();

    $response = $this->actingAs($user)->post(route('oauth.switch-account'), [
        'return' => url($authorize),
    ]);

    $response->assertRedirect($authorize);
    $this->assertGuest();

    $this->get($authorize)->assertRedirect(route('login'));
});

it('rejects switch-account returns that are not the authorize endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('oauth.switch-account'), [
            'return' => 'https://evil.example/phish',
        ])
        ->assertSessionHasErrors('return');

    $this->assertAuthenticatedAs($user);
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
    $response->assertSee('Use a different account');
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

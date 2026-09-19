<?php

use App\Models\User;
use App\Oidc\SigningKey;
use Illuminate\Testing\TestResponse;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = firstPartyClient();
    $this->user->clients()->attach($this->client, ['granted_at' => now()]);

    [$this->verifier, $this->challenge] = pkcePair();
});

/**
 * Drive the authorization endpoint the way a relying party would.
 */
function authorize(array $overrides = []): TestResponse
{
    return test()->get('/oauth/authorize?'.http_build_query([
        'client_id' => test()->client->id,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => 'state-value',
        'nonce' => 'nonce-value',
        'code_challenge' => test()->challenge,
        'code_challenge_method' => 'S256',
        ...$overrides,
    ]));
}

/**
 * Exchange an authorization code for tokens.
 */
function exchange(string $code): TestResponse
{
    return test()->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => test()->client->id,
        'client_secret' => test()->client->plainSecret,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'code_verifier' => test()->verifier,
        'code' => $code,
    ]);
}

it('sends an unauthenticated user to the login page', function () {
    authorize()->assertRedirect(route('login'));
});

it('skips the consent screen for a first-party client', function () {
    $response = $this->actingAs($this->user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $this->client->id,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => 'state-value',
        'nonce' => 'nonce-value',
        'code_challenge' => $this->challenge,
        'code_challenge_method' => 'S256',
    ]));

    $response->assertRedirectContains('http://localhost:8001/auth/accounts/callback');
    $response->assertRedirectContains('state=state-value');

    expect(authorizationCodeFrom($response))->not->toBeEmpty();
});

it('issues an id token that verifies against the published JWKS', function () {
    $this->actingAs($this->user);

    $tokens = exchange(authorizationCodeFrom(authorize()))->assertOk()->json();

    expect($tokens)->toHaveKeys(['access_token', 'refresh_token', 'id_token', 'expires_in']);

    $key = app(SigningKey::class);

    $configuration = Configuration::forAsymmetricSigner(
        new Sha256,
        InMemory::plainText($key->privateKey()),
        InMemory::plainText($key->publicKey()),
    );

    $verified = $configuration->validator()->validate(
        $configuration->parser()->parse($tokens['id_token']),
        new SignedWith($configuration->signer(), $configuration->verificationKey())
    );

    expect($verified)->toBeTrue()
        ->and(decodeJwtHeader($tokens['id_token'])['kid'])->toBe($key->keyId());
});

it('puts the expected claims in the id token', function () {
    $this->actingAs($this->user);

    $tokens = exchange(authorizationCodeFrom(authorize()))->assertOk()->json();

    $claims = decodeJwtPayload($tokens['id_token']);

    expect($claims['iss'])->toBe(config('app.url'))
        ->and($claims['aud'])->toBe($this->client->id)
        ->and($claims['sub'])->toBe($this->user->public_id)
        ->and($claims['nonce'])->toBe('nonce-value')
        ->and($claims['email'])->toBe($this->user->email)
        ->and($claims['email_verified'])->toBeTrue()
        ->and($claims['name'])->toBe($this->user->name)
        ->and($claims['exp'])->toBeGreaterThan($claims['iat']);
});

it('does not leak the primary key as the subject', function () {
    $this->actingAs($this->user);

    $claims = decodeJwtPayload(exchange(authorizationCodeFrom(authorize()))->json('id_token'));

    expect($claims['sub'])->not->toBe((string) $this->user->id);
});

it('binds the id token to the access token with at_hash', function () {
    $this->actingAs($this->user);

    $tokens = exchange(authorizationCodeFrom(authorize()))->assertOk()->json();

    $expected = rtrim(strtr(base64_encode(
        substr(hash('sha256', $tokens['access_token'], true), 0, 16)
    ), '+/', '-_'), '=');

    expect(decodeJwtPayload($tokens['id_token'])['at_hash'])->toBe($expected);
});

it('reports when the user authenticated', function () {
    $this->post(route('login.store'), [
        'email' => $this->user->email,
        'password' => 'password',
    ])->assertRedirect();

    $claims = decodeJwtPayload(exchange(authorizationCodeFrom(authorize()))->json('id_token'));

    expect($claims['auth_time'])->toBeInt()
        ->and($claims['auth_time'])->toBeGreaterThanOrEqual(now()->subMinute()->getTimestamp());
});

it('omits claims the client did not ask for', function () {
    $this->actingAs($this->user);

    $claims = decodeJwtPayload(
        exchange(authorizationCodeFrom(authorize(['scope' => 'openid'])))->json('id_token')
    );

    expect($claims)->not->toHaveKey('email')
        ->and($claims)->not->toHaveKey('name')
        ->and($claims['sub'])->toBe($this->user->public_id);
});

it('issues no id token when openid was not requested', function () {
    $this->actingAs($this->user);

    $tokens = exchange(authorizationCodeFrom(authorize(['scope' => 'profile'])))->assertOk()->json();

    expect($tokens)->toHaveKey('access_token')
        ->and($tokens)->not->toHaveKey('id_token');
});

it('rejects an authorization code replayed with the wrong PKCE verifier', function () {
    $this->actingAs($this->user);

    $code = authorizationCodeFrom(authorize());

    $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $this->client->id,
        'client_secret' => $this->client->plainSecret,
        'redirect_uri' => 'http://localhost:8001/auth/accounts/callback',
        'code_verifier' => 'not-the-verifier-we-started-with-0000000000000000',
        'code' => $code,
    ])->assertStatus(400);
});

it('rejects an authorization code used twice', function () {
    $this->actingAs($this->user);

    $code = authorizationCodeFrom(authorize());

    exchange($code)->assertOk();
    exchange($code)->assertStatus(400);
});

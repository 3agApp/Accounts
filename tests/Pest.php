<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Register a first-party OAuth client, with its plain secret still readable.
 */
function firstPartyClient(string $name = 'SalesReport', string $redirectUri = 'http://localhost:8001/auth/accounts/callback'): Client
{
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient($name, [$redirectUri]);

    $client->forceFill([
        'first_party' => true,
        'post_logout_redirect_uris' => ['http://localhost:8001/'],
    ])->save();

    return $client;
}

/**
 * Register a client that is not ours, and so must ask for consent.
 */
function thirdPartyClient(string $name = 'Some Other App', string $redirectUri = 'https://example.test/callback'): Client
{
    return app(ClientRepository::class)->createAuthorizationCodeGrantClient($name, [$redirectUri]);
}

/**
 * Generate a PKCE verifier and its S256 challenge.
 *
 * @return array{0: string, 1: string}
 */
function pkcePair(): array
{
    $verifier = Str::random(64);

    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    return [$verifier, $challenge];
}

/**
 * Pull the authorization code out of a redirect back to the client.
 */
function authorizationCodeFrom(TestResponse $response): string
{
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    return $query['code'];
}

/**
 * Decode a JWT's payload without verifying it.
 *
 * @return array<string, mixed>
 */
function decodeJwtPayload(string $jwt): array
{
    [, $payload] = explode('.', $jwt);

    return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
}

/**
 * Decode a JWT's header without verifying it.
 *
 * @return array<string, mixed>
 */
function decodeJwtHeader(string $jwt): array
{
    [$header] = explode('.', $jwt);

    return json_decode(base64_decode(strtr($header, '-_', '+/')), true);
}

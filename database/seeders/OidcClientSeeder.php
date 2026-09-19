<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class OidcClientSeeder extends Seeder
{
    private const CLIENT_NAME = 'SalesReport';

    private const REDIRECT_URI = 'http://127.0.0.1:8001/auth/accounts/callback';

    private const TEST_USER_EMAIL = 'test@3ag.local';

    private const TEST_USER_PASSWORD = 'password';

    /**
     * Provision the local first-party OIDC client and a test user, then write both
     * sets of credentials to storage so they can be copied into the client app.
     */
    public function run(ClientRepository $clients): void
    {
        $client = $this->client($clients);
        $user = $this->testUser();

        $credentials = [
            'issuer' => config('app.url'),
            'discovery_url' => rtrim((string) config('app.url'), '/').'/.well-known/openid-configuration',
            'client_id' => (string) $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => self::REDIRECT_URI,
            'scopes' => 'openid profile email',
            'test_user' => [
                'email' => $user->email,
                'password' => self::TEST_USER_PASSWORD,
            ],
        ];

        $json = json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

        File::put(storage_path('app/local-oidc.json'), $json);
        File::put(storage_path('app/oidc-salesreport.json'), $json);

        $this->command?->table(['Key', 'Value'], [
            ['client_id', $credentials['client_id']],
            ['client_secret', $credentials['client_secret']],
            ['redirect_uri', $credentials['redirect_uri']],
            ['test user', $credentials['test_user']['email'].' / '.self::TEST_USER_PASSWORD],
        ]);

        $this->command?->info('Credentials written to storage/app/local-oidc.json');
    }

    /**
     * Create the SalesReport authorization code client, or realign an existing one.
     *
     * Stored secrets are hashed, so re-seeding issues a fresh secret that the client
     * application has to pick up. The plain-text value only exists on this request.
     */
    private function client(ClientRepository $clients): Client
    {
        $existing = Passport::client()->newQuery()->where('name', self::CLIENT_NAME)->first();

        if ($existing === null) {
            return $clients->createAuthorizationCodeGrantClient(
                name: self::CLIENT_NAME,
                redirectUris: [self::REDIRECT_URI],
                confidential: true,
            );
        }

        $existing->forceFill([
            'redirect_uris' => [self::REDIRECT_URI],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ])->save();

        $clients->regenerateSecret($existing);

        return $existing;
    }

    /**
     * Create a pre-verified local test user with a known password.
     */
    private function testUser(): User
    {
        $user = User::firstOrNew(['email' => self::TEST_USER_EMAIL]);

        $user->forceFill([
            'name' => 'Test User',
            'password' => self::TEST_USER_PASSWORD,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}

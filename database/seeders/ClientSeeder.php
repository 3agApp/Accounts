<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

/**
 * Registers the 3AG products as first-party OAuth clients.
 *
 * Safe to re-run: an existing client keeps its id and secret, so seeding again
 * after adding a product does not break the ones already deployed.
 */
class ClientSeeder extends Seeder
{
    public function __construct(protected ClientRepository $clients)
    {
        //
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('products.clients') as $product) {
            $this->seedClient($product['name'], $product['url']);
        }
    }

    /**
     * Create the client for a product if it does not already exist.
     */
    protected function seedClient(string $name, string $baseUrl): void
    {
        $baseUrl = rtrim($baseUrl, '/');
        $redirectUri = $baseUrl.config('products.callback_path');
        $postLogoutUri = $baseUrl.config('products.post_logout_path');

        $existing = Client::query()->where('name', $name)->first();

        if ($existing instanceof Client) {
            $existing->forceFill([
                'redirect_uris' => [$redirectUri],
                'post_logout_redirect_uris' => [$postLogoutUri],
                'first_party' => true,
            ])->save();

            $this->command?->line("  <fg=gray>{$name}</> already registered");

            return;
        }

        $client = $this->clients->createAuthorizationCodeGrantClient($name, [$redirectUri]);

        $client->forceFill([
            'first_party' => true,
            'post_logout_redirect_uris' => [$postLogoutUri],
        ])->save();

        $this->command?->newLine();
        $this->command?->line("  <fg=green>{$name}</> registered");
        $this->command?->line("  <fg=gray>Client ID</>      {$client->id}");
        $this->command?->line("  <fg=gray>Client secret</>  {$client->plainSecret}");
        $this->command?->line('  <fg=yellow>The secret is hashed on save and cannot be shown again.</>');
    }
}

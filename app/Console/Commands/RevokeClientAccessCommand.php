<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAccountsAndClients;
use Illuminate\Console\Command;
use Laravel\Passport\Passport;

/**
 * Stops a user signing in to one of the products, and cuts off the tokens
 * they already hold for it.
 */
class RevokeClientAccessCommand extends Command
{
    use ResolvesAccountsAndClients;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:revoke
                            {email : The account to revoke access from}
                            {client : The client name or id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke a user\'s access to one of the 3AG products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        [$user, $client] = $this->resolve();

        if ($user === null || $client === null) {
            return self::FAILURE;
        }

        $user->clients()->detach($client->getKey());

        // Removing the grant only stops the next sign-in. The tokens they
        // already hold have to go too, and the refresh tokens with them:
        // Passport checks a refresh token's own `revoked` flag and never looks
        // at the access token behind it, so leaving them would let the user
        // keep minting access tokens for the life of the refresh token.
        $tokenIds = Passport::token()->newQuery()
            ->where('user_id', $user->getKey())
            ->where('client_id', $client->getKey())
            ->where('revoked', false)
            ->pluck('id');

        Passport::refreshToken()->newQuery()
            ->whereIn('access_token_id', $tokenIds)
            ->update(['revoked' => true]);

        Passport::token()->newQuery()->whereKey($tokenIds)->update(['revoked' => true]);

        $this->components->info(
            "{$user->email} can no longer sign in to {$client->name}. Revoked {$tokenIds->count()} active token(s)."
        );

        return self::SUCCESS;
    }
}

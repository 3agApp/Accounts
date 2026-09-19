<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAccountsAndClients;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Lets a user sign in to one of the products.
 */
class GrantClientAccessCommand extends Command
{
    use ResolvesAccountsAndClients;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:grant
                            {email : The account to grant access to}
                            {client : The client name or id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant a user access to one of the 3AG products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        [$user, $client] = $this->resolve();

        if ($user === null || $client === null) {
            return self::FAILURE;
        }

        $user->clients()->syncWithoutDetaching([
            $client->getKey() => ['granted_at' => Carbon::now()],
        ]);

        $this->components->info("{$user->email} can now sign in to {$client->name}.");

        return self::SUCCESS;
    }
}

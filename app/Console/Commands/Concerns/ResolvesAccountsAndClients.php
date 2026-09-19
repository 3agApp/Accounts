<?php

namespace App\Console\Commands\Concerns;

use App\Models\Client;
use App\Models\User;
use Laravel\Passport\Passport;

/**
 * Shared argument handling for the commands that manage product access.
 */
trait ResolvesAccountsAndClients
{
    /**
     * Resolve the user and client named by the command's arguments.
     *
     * @return array{0: User|null, 1: Client|null}
     */
    protected function resolve(): array
    {
        $email = (string) $this->argument('email');
        $identifier = (string) $this->argument('client');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error("No account found for [{$email}].");

            return [null, null];
        }

        $client = Passport::client()->newQuery()
            ->where('name', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (! $client instanceof Client) {
            $this->components->error("No client found for [{$identifier}].");

            return [$user, null];
        }

        return [$user, $client];
    }
}

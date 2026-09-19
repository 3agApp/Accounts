<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;

class OAuthClient extends Client
{
    /**
     * First-party clients belong to the 3AG suite itself, so signing in to Accounts is
     * consent enough and the user is never shown the authorization prompt for them.
     *
     * @param  Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty();
    }
}

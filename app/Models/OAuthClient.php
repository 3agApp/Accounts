<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;

class OAuthClient extends Client
{
    /**
     * Always show the authorization prompt so the user can confirm which
     * account continues into the requesting app, or switch accounts first.
     *
     * Suite apps also send prompt=consent on interactive login so this stays
     * true even when the user has previously granted the same scopes.
     *
     * @param  Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return false;
    }
}

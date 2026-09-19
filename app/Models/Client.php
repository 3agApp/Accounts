<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Passport\Client as PassportClient;
use Laravel\Passport\Scope;

class Client extends PassportClient
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grant_types' => 'array',
        'scopes' => 'array',
        'redirect_uris' => 'array',
        'post_logout_redirect_uris' => 'array',
        'personal_access_client' => 'bool',
        'password_client' => 'bool',
        'revoked' => 'bool',
        'first_party' => 'bool',
    ];

    /**
     * The users who have been granted access to this client.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('granted_at');
    }

    /**
     * Determine if the client should skip the authorization prompt.
     *
     * Our own products are trusted, so a signed-in user is never asked to
     * consent to them. Anything else gets the consent screen.
     *
     * @param  Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->first_party;
    }

    /**
     * Get the product's own address, derived from where it asks us to
     * redirect after a sign-in.
     */
    public function homeUrl(): ?string
    {
        $redirectUri = $this->redirect_uris[0] ?? null;

        if ($redirectUri === null) {
            return null;
        }

        $parts = parse_url($redirectUri);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * Determine if the given URI is registered for post-logout redirects.
     */
    public function hasPostLogoutRedirectUri(string $uri): bool
    {
        return in_array($uri, $this->post_logout_redirect_uris ?? [], true);
    }
}

<?php

namespace App\Entities;

use App\Models\User;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use OpenIDConnect\Claims\Traits\WithClaims;
use OpenIDConnect\Interfaces\IdentityEntityInterface;

/**
 * Collects the claims that are embedded in the id_token and returned from /oauth/userinfo.
 *
 * The identity repository instantiates this entity and hands it the authenticated
 * user's identifier, which becomes the `sub` claim of the id_token.
 */
class IdentityEntity implements IdentityEntityInterface
{
    use EntityTrait;
    use WithClaims;

    protected User $user;

    /**
     * @param  mixed  $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
        $this->user = User::findOrFail($identifier);
    }

    /**
     * @param  string[]  $scopes  Optional scope filter, applied by the claim extractor
     * @return array<string, mixed>
     */
    public function getClaims(array $scopes = []): array
    {
        return [
            'email' => $this->user->email,
            'email_verified' => $this->user->hasVerifiedEmail(),
            'name' => $this->user->name,
            'preferred_username' => $this->user->email,
        ];
    }
}

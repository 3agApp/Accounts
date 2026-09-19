<?php

namespace App\Passport;

use App\Oidc\AuthorizeContext;
use Illuminate\Support\Carbon;
use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * Recovers the OIDC parameters stored with the authorization code.
 *
 * `finalizeScopes()` is the one point in the token exchange that is handed the
 * authorization code's identifier, which makes it the natural place to load
 * the nonce and authentication time the ID token needs.
 */
class ScopeRepository extends PassportScopeRepository
{
    public function __construct(
        ClientRepository $clients,
        protected AuthorizeContext $context,
    ) {
        parent::__construct($clients);
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        $authCode = $authCodeId === null
            ? null
            : Passport::authCode()->newQuery()->whereKey($authCodeId)->first();

        // Always written, never merely added to: the refresh grant reaches
        // here with no authorization code, and its ID token must not inherit
        // the nonce of the login that came before it.
        $this->context->remember(
            $authCode?->nonce,
            $authCode?->auth_time ? Carbon::parse($authCode->auth_time) : null,
        );

        return parent::finalizeScopes($scopes, $grantType, $clientEntity, $userIdentifier, $authCodeId);
    }
}

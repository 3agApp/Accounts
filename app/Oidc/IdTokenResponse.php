<?php

namespace App\Oidc;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use SensitiveParameter;

/**
 * Adds an `id_token` to the token endpoint response, which is the one thing
 * that turns Passport's OAuth2 server into an OpenID Connect provider.
 */
class IdTokenResponse extends BearerTokenResponse
{
    public function __construct(protected IdTokenBuilder $idTokens)
    {
        //
    }

    /**
     * {@inheritdoc}
     *
     * Wrapped so that the token serialised into the response body is the same
     * string we hash into the ID token's `at_hash` claim.
     */
    public function setAccessToken(AccessTokenEntityInterface $accessToken): void
    {
        parent::setAccessToken(new MemoizedAccessToken($accessToken));
    }

    /**
     * {@inheritdoc}
     *
     * @return array<array-key, mixed>
     */
    protected function getExtraParams(
        #[SensitiveParameter]
        AccessTokenEntityInterface $accessToken
    ): array {
        if (! $this->grantsOpenIdScope($accessToken)) {
            return [];
        }

        return ['id_token' => $this->idTokens->build($accessToken)];
    }

    /**
     * Determine if the access token was granted the `openid` scope.
     */
    protected function grantsOpenIdScope(AccessTokenEntityInterface $accessToken): bool
    {
        foreach ($accessToken->getScopes() as $scope) {
            if ($scope instanceof ScopeEntityInterface && $scope->getIdentifier() === 'openid') {
                return true;
            }
        }

        return false;
    }
}

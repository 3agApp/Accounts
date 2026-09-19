<?php

namespace App\Passport;

use App\Oidc\AuthorizeSession;
use Laravel\Passport\Bridge\AuthCodeRepository as PassportAuthCodeRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

/**
 * Persists the OIDC parameters of the authorization request alongside the
 * authorization code, so the token endpoint can recover them on the
 * back-channel request where no session exists.
 */
class AuthCodeRepository extends PassportAuthCodeRepository
{
    public function __construct(protected AuthorizeSession $session)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        Passport::authCode()->forceFill([
            'id' => $authCodeEntity->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'scopes' => json_encode($authCodeEntity->getScopes()),
            'nonce' => $this->session->nonce(),
            'auth_time' => $this->session->authenticatedAt(),
            'revoked' => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
        ])->save();

        $this->session->forgetAuthorizeParameters();
    }
}

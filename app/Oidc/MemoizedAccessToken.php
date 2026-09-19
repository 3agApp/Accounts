<?php

namespace App\Oidc;

use DateTimeImmutable;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * An access token that only serialises itself once.
 *
 * League re-signs a fresh JWT on every `toString()` call, stamping it with the
 * current time, so two calls return two different strings. The token response
 * calls it once for the response body and we call it again to compute
 * `at_hash` — without this the hash would describe a token the client never
 * received.
 */
class MemoizedAccessToken implements AccessTokenEntityInterface
{
    protected ?string $serialized = null;

    public function __construct(protected AccessTokenEntityInterface $token)
    {
        //
    }

    public function toString(): string
    {
        return $this->serialized ??= $this->token->toString();
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {
        $this->token->setPrivateKey($privateKey);
    }

    public function getIdentifier(): string
    {
        return $this->token->getIdentifier();
    }

    public function setIdentifier(string $identifier): void
    {
        $this->token->setIdentifier($identifier);
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->token->getExpiryDateTime();
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->token->setExpiryDateTime($dateTime);
    }

    public function setUserIdentifier(string $identifier): void
    {
        $this->token->setUserIdentifier($identifier);
    }

    public function getUserIdentifier(): ?string
    {
        return $this->token->getUserIdentifier();
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->token->getClient();
    }

    public function setClient(ClientEntityInterface $client): void
    {
        $this->token->setClient($client);
    }

    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->token->addScope($scope);
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(): array
    {
        return $this->token->getScopes();
    }
}

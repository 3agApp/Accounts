<?php

namespace App\Oidc;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Contracts\Config\Repository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use RuntimeException;

/**
 * Builds the signed ID token that accompanies an access token issued for the
 * `openid` scope.
 */
class IdTokenBuilder
{
    protected ?Configuration $configuration = null;

    public function __construct(
        protected SigningKey $key,
        protected AuthorizeContext $context,
        protected Repository $config,
    ) {
        //
    }

    /**
     * Build a signed ID token for the given access token.
     */
    public function build(AccessTokenEntityInterface $accessToken): string
    {
        $user = User::query()->find($accessToken->getUserIdentifier());

        if (! $user instanceof User) {
            throw new RuntimeException('Unable to issue an ID token for an unknown user.');
        }

        $scopes = $this->scopeIdentifiers($accessToken);
        $issuedAt = new DateTimeImmutable;

        $builder = $this->configuration()->builder()
            ->withHeader('kid', $this->key->keyId())
            ->issuedBy($this->issuer())
            ->permittedFor($accessToken->getClient()->getIdentifier())
            ->relatedTo($user->public_id)
            ->issuedAt($issuedAt)
            ->expiresAt($accessToken->getExpiryDateTime())
            ->withClaim('at_hash', $this->accessTokenHash($accessToken));

        if ($this->context->authenticatedAt !== null) {
            $builder = $builder->withClaim('auth_time', $this->context->authenticatedAt->getTimestamp());
        }

        // A token re-issued through the refresh grant has no authorization
        // request behind it, so it carries no nonce. That is what the spec asks
        // for, and relying parties only check the nonce on a fresh login.
        if ($this->context->nonce !== null) {
            $builder = $builder->withClaim('nonce', $this->context->nonce);
        }

        foreach ($this->claimsFor($user, $scopes) as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }

        return $builder->getToken(
            $this->configuration()->signer(),
            $this->configuration()->signingKey()
        )->toString();
    }

    /**
     * Get the claims the granted scopes entitle the client to.
     *
     * @param  string[]  $scopes
     * @return array<string, mixed>
     */
    public function claimsFor(User $user, array $scopes): array
    {
        $claims = [];

        if (in_array('profile', $scopes, true)) {
            $claims['name'] = $user->name;
            $claims['updated_at'] = $user->updated_at?->getTimestamp();
        }

        if (in_array('email', $scopes, true)) {
            $claims['email'] = $user->email;
            $claims['email_verified'] = $user->hasVerifiedEmail();
        }

        return $claims;
    }

    /**
     * Get the issuer identifier, which must match the discovery document.
     */
    public function issuer(): string
    {
        return rtrim((string) $this->config->get('app.url'), '/');
    }

    /**
     * Get the scope identifiers granted to the given access token.
     *
     * @return string[]
     */
    protected function scopeIdentifiers(AccessTokenEntityInterface $accessToken): array
    {
        return array_map(
            fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $accessToken->getScopes()
        );
    }

    /**
     * Hash the access token so the client can tie it to this ID token.
     *
     * Per OIDC core this is the base64url encoded left-most half of the
     * SHA-256 digest, the hash matching the RS256 signing algorithm.
     */
    protected function accessTokenHash(AccessTokenEntityInterface $accessToken): string
    {
        $digest = hash('sha256', $accessToken->toString(), true);

        return rtrim(strtr(base64_encode(substr($digest, 0, 16)), '+/', '-_'), '=');
    }

    /**
     * Get the JWT configuration bound to Passport's key pair.
     */
    protected function configuration(): Configuration
    {
        return $this->configuration ??= Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($this->key->privateKey()),
            InMemory::plainText($this->key->publicKey()),
        );
    }
}

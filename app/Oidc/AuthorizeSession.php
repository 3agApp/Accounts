<?php

namespace App\Oidc;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;

/**
 * The OIDC state we keep in the user's session.
 *
 * `nonce` and `max_age` arrive as query parameters on the authorization
 * request but are needed again after the consent screen POSTs back, and
 * `auth_time` has to outlive the request the user actually logged in on.
 */
class AuthorizeSession
{
    protected const NONCE = 'oidc.nonce';

    protected const MAX_AGE = 'oidc.max_age';

    protected const AUTHENTICATED_AT = 'oidc.authenticated_at';

    public function __construct(protected Session $session)
    {
        //
    }

    /**
     * Record the parameters of the authorization request being processed.
     */
    public function rememberAuthorizeParameters(?string $nonce, ?int $maxAge): void
    {
        $this->session->put(self::NONCE, $nonce);
        $this->session->put(self::MAX_AGE, $maxAge);
    }

    /**
     * Get the nonce of the authorization request being processed.
     */
    public function nonce(): ?string
    {
        return $this->session->get(self::NONCE);
    }

    /**
     * Get the `max_age` of the authorization request being processed.
     */
    public function maxAge(): ?int
    {
        return $this->session->get(self::MAX_AGE);
    }

    /**
     * Forget the parameters of the authorization request being processed.
     */
    public function forgetAuthorizeParameters(): void
    {
        $this->session->forget([self::NONCE, self::MAX_AGE]);
    }

    /**
     * Record the moment the user authenticated with this session.
     */
    public function markAuthenticatedNow(): void
    {
        $this->session->put(self::AUTHENTICATED_AT, Carbon::now()->getTimestamp());
    }

    /**
     * Get the moment the user authenticated with this session.
     */
    public function authenticatedAt(): ?Carbon
    {
        $timestamp = $this->session->get(self::AUTHENTICATED_AT);

        return $timestamp === null ? null : Carbon::createFromTimestamp($timestamp);
    }

    /**
     * Determine if the session is older than the given `max_age` allows.
     */
    public function isStalerThan(int $maxAge): bool
    {
        $authenticatedAt = $this->authenticatedAt();

        return $authenticatedAt === null
            || $authenticatedAt->addSeconds($maxAge)->isPast();
    }
}

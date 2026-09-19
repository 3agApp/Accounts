<?php

namespace App\Oidc;

use Illuminate\Support\Carbon;

/**
 * Carries the OIDC parameters that have to survive the hop from the
 * authorization request to the back-channel token request.
 *
 * The authorization code row holds them between the two requests; this object
 * holds them for the duration of the token request itself.
 */
class AuthorizeContext
{
    public ?string $nonce = null;

    public ?Carbon $authenticatedAt = null;

    /**
     * Record the values belonging to the authorization code being exchanged.
     */
    public function remember(?string $nonce, ?Carbon $authenticatedAt): void
    {
        $this->nonce = $nonce;
        $this->authenticatedAt = $authenticatedAt;
    }
}

<?php

namespace App\Http\Controllers\Oidc;

use App\Models\Client;
use App\Oidc\IdTokenBuilder;
use App\Oidc\SigningKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Throwable;

/**
 * RP-initiated logout. A product app sends the user here to end their session
 * at the identity provider, not just locally.
 */
class EndSessionController
{
    public function __construct(
        protected SigningKey $key,
        protected IdTokenBuilder $idTokens,
    ) {
        //
    }

    public function __invoke(Request $request): RedirectResponse
    {
        // Work out where to send the user before the session goes away, since
        // resolving the client depends on nothing but the token hint.
        $destination = $this->destinationFor($request);

        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($destination);
    }

    /**
     * Resolve where to send the user after logging out.
     *
     * A redirect target is only honoured when the ID token hint proves which
     * client is asking and that client has the URI registered. Anything else
     * lands back on our own dashboard, so this endpoint can never be used as
     * an open redirect.
     */
    protected function destinationFor(Request $request): string
    {
        $requested = $request->query('post_logout_redirect_uri');

        if (! is_string($requested) || $requested === '') {
            return route('dashboard');
        }

        $client = $this->clientFromTokenHint($request->query('id_token_hint'));

        if (! $client instanceof Client || ! $client->hasPostLogoutRedirectUri($requested)) {
            return route('dashboard');
        }

        $state = $request->query('state');

        return is_string($state) && $state !== ''
            ? $requested.(str_contains($requested, '?') ? '&' : '?').http_build_query(['state' => $state])
            : $requested;
    }

    /**
     * Identify the client that issued the given ID token.
     */
    protected function clientFromTokenHint(mixed $hint): ?Client
    {
        if (! is_string($hint) || $hint === '') {
            return null;
        }

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($this->key->privateKey()),
            InMemory::plainText($this->key->publicKey()),
        );

        try {
            $token = $configuration->parser()->parse($hint);

            $configuration->validator()->assert(
                $token,
                new SignedWith($configuration->signer(), $configuration->verificationKey()),
                new IssuedBy($this->idTokens->issuer()),
            );
        } catch (Throwable) {
            return null;
        }

        if (! $token instanceof UnencryptedToken) {
            return null;
        }

        $audience = $token->claims()->get('aud', []);
        $clientId = is_array($audience) ? ($audience[0] ?? null) : $audience;

        if (! is_string($clientId)) {
            return null;
        }

        $client = Passport::client()->newQuery()->whereKey($clientId)->first();

        return $client instanceof Client ? $client : null;
    }
}

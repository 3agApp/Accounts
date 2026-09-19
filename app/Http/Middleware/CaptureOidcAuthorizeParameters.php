<?php

namespace App\Http\Middleware;

use App\Oidc\AuthorizeSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Carries `nonce` and `max_age` from the authorization request into the
 * session, where they survive the consent screen's POST back.
 */
class CaptureOidcAuthorizeParameters
{
    public function __construct(protected AuthorizeSession $session)
    {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.authorizations.authorize')) {
            return $next($request);
        }

        $maxAge = $request->has('max_age') ? max(0, $request->integer('max_age')) : null;

        $this->session->rememberAuthorizeParameters($request->query('nonce'), $maxAge);

        // A client asking for a fresh authentication gets one. Passport already
        // knows how to force a re-login for `prompt=login`, so we reuse it
        // rather than logging the user out ourselves.
        if ($maxAge !== null && $this->session->isStalerThan($maxAge)) {
            $request->query->set('prompt', trim($request->query('prompt', '').' login'));
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep an unverified address from being handed to a suite app.
 *
 * Registration signs the new user in before they have opened the link, so
 * without this they could walk straight into the authorization screen and
 * leave with tokens for an address they have never proved they hold. The
 * apps downstream match invitations and pre-existing accounts on that
 * address, so the claim has to be true before it travels.
 *
 * This sits on the whole Passport route group, which also carries the back
 * channel: /oauth/token and /oauth/userinfo are called by the client, not a
 * browser, and have no session to read. They resolve no user here and pass
 * straight through, which is why this tests for a user rather than demanding
 * one the way the framework's `verified` middleware does.
 */
class EnsureEmailIsVerifiedForOAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}

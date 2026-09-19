<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Passport;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Gates which product each user may sign in to.
 *
 * Authentication says who you are; this says where you are allowed to go. A
 * user with no grant for a client never reaches the consent screen and never
 * receives an authorization code.
 */
class EnsureUserCanAccessClient
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.authorizations.authorize', 'passport.authorizations.approve')) {
            return $next($request);
        }

        $user = $request->user();
        $clientId = $this->clientId($request);

        // An unauthenticated user is Passport's problem, not ours: it will send
        // them to the login page and bring them back here afterwards.
        if ($user === null || $clientId === null) {
            return $next($request);
        }

        $client = Passport::client()->newQuery()->whereKey($clientId)->first();

        if ($client instanceof Client && ! $user->canAccessClient($client)) {
            return response()->view('oauth.access-denied', ['client' => $client], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /**
     * Resolve the client the request is about.
     *
     * The authorization request carries the client in the query string; the
     * approval that follows it only has the request stashed in the session.
     */
    protected function clientId(Request $request): ?string
    {
        if ($request->filled('client_id')) {
            return $request->string('client_id')->toString();
        }

        try {
            $authRequest = unserialize((string) $request->session()->get('authRequest'), ['allowed_classes' => [
                AuthorizationRequest::class,
                \Laravel\Passport\Bridge\Client::class,
                Scope::class,
                User::class,
            ]]);
        } catch (Throwable) {
            return null;
        }

        return $authRequest instanceof AuthorizationRequestInterface
            ? $authRequest->getClient()->getIdentifier()
            : null;
    }
}

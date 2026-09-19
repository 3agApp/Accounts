<?php

namespace App\Providers;

use App\Models\Client;
use App\Oidc\AuthorizeContext;
use App\Oidc\IdTokenResponse;
use App\Passport\AuthCodeRepository;
use App\Passport\ScopeRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge;
use Laravel\Passport\Passport;

/**
 * Configures Passport as an OpenID Connect provider for the 3AG products.
 */
class PassportServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One context per request, shared by the bridge that reads the
        // authorization code and the builder that writes the ID token.
        $this->app->singleton(AuthorizeContext::class);

        // Both bridges extend Passport's own, adding only the handling of the
        // OIDC parameters that have to survive the authorization code.
        $this->app->singleton(Bridge\AuthCodeRepository::class, AuthCodeRepository::class);
        $this->app->singleton(Bridge\ScopeRepository::class, ScopeRepository::class);

        // Routes are registered when Passport boots, which is before this
        // provider boots, so the grant has to be switched off during register.
        Passport::$deviceCodeGrantEnabled = false;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::useClientModel(Client::class);

        Passport::useAuthorizationServerResponseType(
            $this->app->make(IdTokenResponse::class)
        );

        Passport::authorizationView('oauth.authorize');

        Passport::tokensCan([
            'openid' => 'Verify your identity',
            'profile' => 'Read your name',
            'email' => 'Read your email address',
        ]);

        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}

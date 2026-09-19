# 3AG Accounts

The identity provider for the 3AG products. A client signs in once at
[accounts.3ag.app](https://accounts.3ag.app), and SalesReport,
ProductSyncManager and CompliancePlatform sign them in from there over OpenID
Connect.

This app owns identity and nothing else. Each product keeps its own codebase,
its own database, its own `APP_KEY` and its own host-only session cookie. The
only thing that crosses the boundary is a signed ID token.

## How it works

Built on [Laravel Passport](https://laravel.com/docs/passport) for the OAuth 2.1
machinery, with a small OIDC layer on top of it in `app/Oidc`, because Passport
ships no ID tokens, discovery document, JWKS or userinfo endpoint of its own.

| Endpoint | Purpose |
| --- | --- |
| `GET /.well-known/openid-configuration` | Provider metadata |
| `GET /oauth/authorize` | Authorization request (Passport) |
| `POST /oauth/token` | Token exchange (Passport) |
| `GET /oauth/userinfo` | Claims for an access token |
| `GET /oauth/jwks` | Public signing key |
| `GET /oauth/logout` | RP-initiated logout |

Supported: authorization code with PKCE (`S256`) and refresh tokens. ID tokens
are RS256, signed with Passport's key pair. Scopes are `openid`, `profile` and
`email`.

The `sub` claim is the user's `public_id`, a ULID — not the primary key. A
product should store that and treat it as the identity, because it survives a
change of name or email address.

### Two things worth knowing

**First-party clients skip the consent screen.** A client with `first_party`
set is one of ours, and a signed-in user is sent straight back to it. Any other
client gets the Blade consent page at `resources/views/oauth/authorize.blade.php`.

**Access is granted per product.** A user with no row in `client_user` for a
client cannot sign in to it, and is shown "You don't have access to X" instead
of an authorization code. Products still own their own roles and permissions;
this is only the front door.

## Running an account

There is no public registration.

```bash
php artisan accounts:create-user ada@example.com --name="Ada Lovelace" --grant=SalesReport
php artisan accounts:grant ada@example.com ProductSyncManager
php artisan accounts:revoke ada@example.com SalesReport
```

`accounts:create-user` emails a verification link and a link to set a password.
`accounts:revoke` removes the grant *and* revokes the tokens the user already
holds for that product, so it takes effect immediately rather than at their next
sign-in.

## Local development

MySQL, same as production. Create the database, then:

```bash
composer setup
php artisan db:seed
composer run dev
```

The seeder registers the three products as first-party clients and prints each
client id and secret **once** — the secret is hashed on save. It expects
Accounts on port 8000 and the products on 8001–8003; override with
`CLIENT_SALESREPORT_URL` and friends.

In local mode it also creates `test@example.com` / `password`, with access to
every product.

The test suite runs on in-memory SQLite for speed (`phpunit.xml`), but CI runs
it against MySQL, because that is what production is. Real environment
variables beat the ones in `phpunit.xml`, so you can do the same locally:

```bash
DB_CONNECTION=mysql DB_DATABASE=accounts_test php artisan test --compact
```

Point it at a database of its own. `RefreshDatabase` starts by dropping every
table, so aiming it at `accounts` costs you your development data and reissues
every client secret.

## Deployment notes

- **The signing keys must be stable.** Generate them once and put them in the
  server's `.env` as `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` (PEM, with
  literal `\n` for line breaks). Keys under `storage/` would work too, but env
  keeps them in one place with the rest of the secrets. Rotating them
  invalidates every live token and every published JWK.
- **`SESSION_DOMAIN` stays `null`.** A host-only cookie on `accounts.3ag.app`.
  Setting it to `.3ag.app` would share this app's session with every product,
  which is the thing this design exists to avoid.
- **MySQL, not SQLite.** `deploy.php` shares only `storage` and `.env`, so a
  SQLite file under `database/` would be replaced on every release, taking
  every identity with it. Create the database and a user for it on the server,
  and set `DB_*` in the shared `.env`. The first deploy needs
  `php artisan migrate --force` against the empty schema.

## Adding a product

The client side is about a hundred lines. SalesReport has a working copy to
crib from: `app/Services/Auth/ThreeAgProvider.php`,
`app/Http/Controllers/Auth/ThreeAgCallbackController.php` and
`app/Http/Middleware/ThreeAgSingleSignOn.php`.

1. **Register the client here.** Add the product to `config/products.php` and
   run `php artisan db:seed --class=ClientSeeder`. Keep the printed secret.

2. **In the product**, install `laravel/socialite` and add a
   `Socialite\Two\AbstractProvider` pointing at this app's endpoints, with
   `$usesPKCE = true` and scopes `openid profile email`. Register it with
   `Socialite::extend('3ag', ...)`.

3. **Add `users.oidc_sub`**, a nullable unique string, and two routes:
   `GET /auth/accounts/redirect` and `GET /auth/accounts/callback`.

4. **In the callback**, resolve the local user in this order: by `oidc_sub`;
   then, *only if the provider reports `email_verified`*, by email, backfilling
   `oidc_sub`; then create one. Skipping the `email_verified` check would let
   anyone who can set an unverified address here claim an existing account in
   the product.

5. **Configure** `THREE_AG_BASE_URL`, `THREE_AG_CLIENT_ID` and
   `THREE_AG_CLIENT_SECRET`. Leave `THREE_AG_SSO_ONLY=false` until the flow is
   proven in production; turning it on removes the product's own login,
   registration and password reset, and makes logging out end the session here
   as well.

6. **Grant access**: `php artisan accounts:grant someone@example.com TheProduct`.
   Until you do, they will be refused — which is the point.

## Tests

```bash
php artisan test --compact
```

The suite covers the whole flow rather than the pieces: a real authorization
request through to an ID token verified against the published JWKS, the consent
screen appearing for third parties and not for us, the access gate refusing to
issue a code, `nonce` surviving the consent POST, refreshed tokens correctly
*not* carrying a nonce, and the logout endpoint honouring only registered
redirect URIs.

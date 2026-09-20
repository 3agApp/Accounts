<?php

use App\Http\Middleware\EnsureEmailIsVerifiedForOAuth;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

test('email verification is one of the enabled features', function () {
    expect(Features::enabled(Features::emailVerification()))->toBeTrue();
});

test('registering leaves the address unverified and sends the link', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@3ag.local',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ]);

    $user = User::query()->where('email', 'ada@3ag.local')->firstOrFail();

    expect($user->email_verified_at)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('registering fires Registered, which is what sends the link', function () {
    Event::fake();

    $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@3ag.local',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ]);

    Event::assertDispatched(Registered::class);
});

test('an unverified user is held at the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/home')->assertRedirect(route('verification.notice'));
});

test('opening the link verifies the address', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(config('fortify.home').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('changing the email address requires verifying the new one', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', [
        'name' => $user->name,
        'email' => 'moved@3ag.local',
    ])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->email)->toBe('moved@3ag.local')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('leaving the email address alone keeps it verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', [
        'name' => 'A New Name',
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    Notification::assertNothingSent();
});

test('a tampered link does not verify the address', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->getKey(),
        'hash' => sha1('someone-elses@3ag.local'),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The OAuth gate
|--------------------------------------------------------------------------
|
| Registration signs the new user in before they have opened the link, so the
| authorization screen is the door that actually has to be shut.
|
| Passport applies config('passport.middleware') as group middleware, which
| puts the gate ahead of the `web` group -- and so ahead of the session it
| reads. bootstrap/app.php moves it back with appendToPriorityList(), and
| without that the gate resolves a null user and waves everyone through.
|
| The two request tests below do NOT catch that on their own: Laravel's test
| client keeps one session store alive across the requests in a test, so by
| the time the gate runs the store is already started and the user resolves
| either way. Verified against a real server instead -- unverified user, cold
| request: 302 to /email/verify with the priority entry, 200 without it. The
| ordering test is the regression guard that works in this suite.
|
*/

test('the gate is ordered after the session it reads', function () {
    // The priority list is assembled when the HTTP kernel bootstraps, so it
    // is empty until something has actually been routed.
    $this->get('/login');

    $priority = app('router')->middlewarePriority;

    $session = array_search(StartSession::class, $priority, true);
    $gate = array_search(EnsureEmailIsVerifiedForOAuth::class, $priority, true);

    expect($session)->not->toBeFalse('StartSession is missing from the priority list')
        ->and($gate)->not->toBeFalse('the OAuth gate is missing from the priority list -- Passport would run it before the session and it would pass everyone through')
        ->and($gate)->toBeGreaterThan($session);
});

/**
 * Sign in the way a browser does, so the user is read back out of the session.
 *
 * actingAs() would not do: it puts the user straight on the guard, so the
 * gate finds one whether or not the session has started by the time it runs
 * -- which is the exact mistake these tests exist to catch.
 */
function signInThroughTheLoginForm(User $user): void
{
    test()->post('/login', ['email' => $user->email, 'password' => 'password']);

    test()->assertAuthenticatedAs($user);
}

function suiteClientRedirectUri(): string
{
    return 'https://productsyncmanager.test/auth/accounts/callback';
}

function suiteClient(): Client
{
    return app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'ProductSync',
        redirectUris: [suiteClientRedirectUri()],
    );
}

test('an unverified user cannot reach the authorization screen', function () {
    $client = suiteClient();

    signInThroughTheLoginForm(User::factory()->unverified()->create());

    $this->get(authorizeUrl($client->getKey(), suiteClientRedirectUri()))
        ->assertRedirect(route('verification.notice'));
});

test('a verified user still reaches the authorization screen', function () {
    $client = suiteClient();

    signInThroughTheLoginForm(User::factory()->create());

    $this->get(authorizeUrl($client->getKey(), suiteClientRedirectUri()))
        ->assertOk();
});

test('the back channel is untouched by the gate', function () {
    // No session, so no user to read: the token endpoint has to answer on its
    // own terms rather than being redirected to a login page.
    $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'nonsense',
        'code' => 'nonsense',
    ])->assertJson(fn ($json) => $json->etc());
});

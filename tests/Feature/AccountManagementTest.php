<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('offers no public registration', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

it('shows the sign-in page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Sign in')
        ->assertSee('Accounts are created by an administrator');
});

it('sends a signed-in user to their dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
});

it('lists only the products the user was granted', function () {
    $user = User::factory()->create();
    $salesReport = firstPartyClient();
    firstPartyClient('ProductSyncManager', 'http://localhost:8002/auth/accounts/callback');

    $user->clients()->attach($salesReport, ['granted_at' => now()]);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSee('SalesReport')
        ->assertDontSee('ProductSyncManager');
});

it('keeps an unverified user off the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/email/verify');
});

it('creates an account from the console and invites the person to set a password', function () {
    Notification::fake();

    $this->artisan('accounts:create-user', [
        'email' => 'ada@example.com',
        '--name' => 'Ada Lovelace',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($user->name)->toBe('Ada Lovelace')
        ->and($user->public_id)->not->toBeEmpty()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('grants products while creating an account', function () {
    Notification::fake();

    $client = firstPartyClient();

    $this->artisan('accounts:create-user', [
        'email' => 'ada@example.com',
        '--name' => 'Ada Lovelace',
        '--grant' => ['SalesReport'],
    ])->assertSuccessful();

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($user->canAccessClient($client))->toBeTrue();
});

it('refuses to create a duplicate account', function () {
    Notification::fake();

    User::factory()->create(['email' => 'ada@example.com']);

    $this->artisan('accounts:create-user', [
        'email' => 'ada@example.com',
        '--name' => 'Ada Lovelace',
    ])->assertFailed();

    expect(User::query()->where('email', 'ada@example.com')->count())->toBe(1);
});

it('gives every account an opaque public id', function () {
    $user = User::factory()->create();

    expect($user->public_id)->toBeString()->toHaveLength(26)
        ->and($user->public_id)->not->toBe((string) $user->id);
});

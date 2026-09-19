<?php

use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Ada Lovelace']);
});

it('refuses an unauthenticated request', function () {
    $this->getJson('/oauth/userinfo')->assertUnauthorized();
});

it('answers a browser-shaped request with 401 rather than a login page', function () {
    $this->get('/oauth/userinfo', ['Accept' => 'text/html'])->assertUnauthorized();
});

it('refuses a token without the openid scope', function () {
    Passport::actingAs($this->user, ['profile']);

    $this->getJson('/oauth/userinfo')->assertForbidden();
});

it('returns the opaque subject for an openid token', function () {
    Passport::actingAs($this->user, ['openid']);

    $this->getJson('/oauth/userinfo')
        ->assertOk()
        ->assertExactJson(['sub' => $this->user->public_id]);
});

it('returns profile claims when the profile scope was granted', function () {
    Passport::actingAs($this->user, ['openid', 'profile']);

    $this->getJson('/oauth/userinfo')
        ->assertOk()
        ->assertJsonPath('name', 'Ada Lovelace')
        ->assertJsonMissingPath('email');
});

it('returns email claims when the email scope was granted', function () {
    Passport::actingAs($this->user, ['openid', 'email']);

    $this->getJson('/oauth/userinfo')
        ->assertOk()
        ->assertJsonPath('email', $this->user->email)
        ->assertJsonPath('email_verified', true)
        ->assertJsonMissingPath('name');
});

it('reports an unverified email as unverified', function () {
    $user = User::factory()->unverified()->create();

    Passport::actingAs($user, ['openid', 'email']);

    $this->getJson('/oauth/userinfo')
        ->assertOk()
        ->assertJsonPath('email_verified', false);
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the root path sends a guest to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('the root path sends a signed-in user to their applications', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('home'));
});

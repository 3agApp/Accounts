<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects a guest to the login page', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

it('lists only the applications that have a configured url', function () {
    config()->set('suite.apps', [
        ['name' => 'SalesReport', 'description' => 'Sales reporting.', 'url' => 'http://127.0.0.1:8001'],
        ['name' => 'ProductSync', 'description' => 'Catalogue sync.', 'url' => null],
    ]);

    $response = $this->actingAs(User::factory()->create())->get(route('home'));

    $response->assertOk();
    $response->assertSee('SalesReport');
    $response->assertSee('http://127.0.0.1:8001', escape: false);
    $response->assertDontSee('ProductSync');
});

it('explains how to configure applications when none are set', function () {
    config()->set('suite.apps', [
        ['name' => 'SalesReport', 'description' => 'Sales reporting.', 'url' => null],
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('No applications are configured yet.');
});

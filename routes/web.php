<?php

use App\Http\Controllers\Auth\SwitchOAuthAccountController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('home') : redirect()->route('login'));

// `verified` as well as `auth`: an address nobody has proved they hold should
// not be picking an app to sign in to, nor switching which account does.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', HomeController::class)->name('home');

    Route::post('/oauth/switch-account', SwitchOAuthAccountController::class)
        ->name('oauth.switch-account');
});

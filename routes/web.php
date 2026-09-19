<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Oidc\DiscoveryController;
use App\Http\Controllers\Oidc\EndSessionController;
use App\Http\Controllers\Oidc\JwksController;
use App\Http\Controllers\Oidc\UserInfoController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| OpenID Connect Discovery
|--------------------------------------------------------------------------
|
| Passport provides /oauth/authorize and /oauth/token; these are the OIDC
| endpoints layered on top of it. The discovery document is what a relying
| party reads to find all of them.
|
*/

Route::get('/.well-known/openid-configuration', DiscoveryController::class)->name('oidc.discovery');
Route::get('/oauth/jwks', JwksController::class)->name('oidc.jwks');
Route::get('/oauth/logout', EndSessionController::class)->middleware('web')->name('oidc.logout');

Route::get('/oauth/userinfo', UserInfoController::class)
    ->middleware(['auth:api', CheckToken::using('openid')])
    ->name('oidc.userinfo');

/*
|--------------------------------------------------------------------------
| Account Management
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/settings', SettingsController::class)->name('settings');
});

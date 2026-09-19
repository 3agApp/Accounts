<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the signed-in user the 3AG applications they can open.
     */
    public function __invoke(Request $request): View
    {
        $apps = collect(config('suite.apps'))
            ->filter(fn (array $app): bool => filled($app['url']))
            ->values()
            ->all();

        return view('home', [
            'user' => $request->user(),
            'apps' => $apps,
        ]);
    }
}

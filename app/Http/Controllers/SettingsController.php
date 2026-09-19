<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The account's own security settings. The forms post to Fortify's endpoints.
 */
class SettingsController
{
    public function __invoke(Request $request): View
    {
        return view('settings', [
            'user' => $request->user(),
        ]);
    }
}

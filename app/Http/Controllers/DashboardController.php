<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shows the products this user can sign in to.
 */
class DashboardController
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', [
            'clients' => $request->user()->clients()->orderBy('name')->get(),
        ]);
    }
}

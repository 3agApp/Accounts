<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SwitchOAuthAccountController extends Controller
{
    /**
     * Sign out of Accounts and return to the OAuth authorize URL so another
     * account can continue into the requesting app.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $returnTo = $this->validatedAuthorizeReturnUrl($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($returnTo);
    }

    /**
     * Only allow returning to this app's OAuth authorize endpoint.
     */
    private function validatedAuthorizeReturnUrl(Request $request): string
    {
        $validator = Validator::make($request->all(), [
            'return' => ['required', 'string', 'url'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        /** @var string $returnTo */
        $returnTo = $validator->validated()['return'];
        $parts = parse_url($returnTo);

        $host = $parts['host'] ?? null;
        $path = $parts['path'] ?? '';
        $scheme = $parts['scheme'] ?? null;

        if ($host !== $request->getHost()
            || ($scheme !== null && $scheme !== $request->getScheme())
            || $path !== '/oauth/authorize'
        ) {
            throw ValidationException::withMessages([
                'return' => __('The return URL must be this app\'s OAuth authorize endpoint.'),
            ]);
        }

        return $returnTo;
    }
}

<?php

namespace Agriserv\SSO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SsoAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        // Custom authentication logic for SSO
        if (!auth()->check()) {
            session()->put('url.intended', url()->current());

            return redirect()->route('auth.sso');
        }

        if ($intendedUrl = session('url.intended')) {
            session()->forget('url.intended'); // Clear the intended URL
            return redirect()->to($intendedUrl);
        }

        return $next($request);
    }
}

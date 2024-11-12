<?php

namespace Agriserv\SSO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SsoAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            $request->session()->put('previousUrl', $request->url());
            return redirect()->route('auth.sso');
        }

        if ($intendedUrl = session('previousUrl')) {
            session()->forget('previousUrl'); // Clear the intended URL
            return redirect()->to($intendedUrl);
        }

        return $next($request);
    }
}

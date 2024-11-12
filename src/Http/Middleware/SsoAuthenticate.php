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
            return redirect()->route('auth.sso');
        }

        return $next($request);
    }
}

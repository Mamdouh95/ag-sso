<?php

namespace Agriserv\SSO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SsoAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            // Only remember navigable pages, keeping the query string
            if ($request->isMethod('GET') && !$request->expectsJson()) {
                $request->session()->put('previousUrl', $request->fullUrl());
            }

            return redirect()->route('auth.sso');
        }

        return $next($request);
    }
}

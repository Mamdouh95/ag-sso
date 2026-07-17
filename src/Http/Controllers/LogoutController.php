<?php

namespace Agriserv\SSO\Http\Controllers;

use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function store(Request $request)
    {
        // Handle logout logic, e.g., clear session or revoke token
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(config('sso.logout_url'));
    }
}

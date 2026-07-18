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

        // Without a portal logout URL the SSO session survives and silently
        // signs the user back in on the next protected page
        $logoutUrl = config('sso.logout_url');

        return $logoutUrl ? redirect()->away($logoutUrl) : redirect()->to('/');
    }
}

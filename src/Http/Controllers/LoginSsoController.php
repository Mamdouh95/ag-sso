<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginSsoController extends \Agriserv\SSO\Http\Controllers\SsoController
{
    /**
     * Handle the fetched user info.
     *
     * @param array $userInfo
     * @return mixed
     */
    protected function handleUserInfo(array $userInfo)
    {
        $userModel = app(config('sso.user_model'));

        // Create or update the user
        $user = $userModel::updateOrCreate([
            'sso_id' => $userInfo['id'],
        ], [
            'email' => $userInfo['email'] ?? null,
            'name' => $userInfo['full_name'] ?? null,
        ]);

        // Dynamically assign roles
        $this->syncUserRoles($user, $userInfo['roles'] ?? []);

        // Log the user in with a fresh session id to prevent session fixation
        Auth::login($user);
        request()->session()->regenerate();

        // Redirect back to the page the user originally requested
        return redirect()->to(session()->pull('previousUrl', '/'));
    }

    /**
     * Sync roles with the local user.
     */
    private function syncUserRoles($user, array $roles)
    {
        if (method_exists($user, 'syncRoles')) {
            try {
                $user->syncRoles($roles);
            } catch (\Exception|\Throwable $e) {
                Log::warning("Failed to sync roles: " . $e->getMessage());
            }
        }
    }
}

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
            'sso_id' => $userData['sso_id'] ?? $userInfo['id'],
        ], $userData);

        // Dynamically assign roles
        $this->syncUserRoles($user, $userInfo['roles']);

        // Log the user in
        Auth::login($user);

        // Redirect after login
        return redirect()->to(session('previousUrl') ?? '/');
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

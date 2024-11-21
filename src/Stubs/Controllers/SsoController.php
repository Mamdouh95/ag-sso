<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SsoController extends \Agriserv\SSO\Http\Controllers\SsoController
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
            'sso_id' => $userData['sso_id'] ?? $userInfo['service']['items']['id'],
        ], $userData);

        // Dynamically assign roles
        if (!empty($userInfo['service']['items']['roles'])) {
            $this->syncUserRoles($user, $userInfo['service']['items']['roles']);
        }

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

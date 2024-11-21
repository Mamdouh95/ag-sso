<?php

namespace Agriserv\SSO\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserFetchedFromSso
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array $userInfo
     * @return void
     */
    public function __construct(array $userInfo)
    {
        $userModel = app(config('sso.user_model'));

        $user = $userModel::updateOrCreate([
            'sso_id' => $userData['sso_id'] ?? $userInfo['service']['items']['id'],
        ], $userData);

        // Dynamically assign roles if provided
        if (!empty($userInfo['service']['items']['roles'])) {
            $this->syncUserRoles($user, $userInfo['service']['items']['roles']);
        }

        // Log the user in
        Auth::login($user);
    }

    private function syncUserRoles($user, array $roles)
    {
        if (method_exists($user, 'syncRoles')) {
            try {
                $user->syncRoles($roles);
            } catch (\Exception|\Throwable $e) {
                Log::warning("Failed to sync roles: " . $e->getMessage());
            }
        } else {
            Log::info("User model does not support role synchronization.");
        }
    }
}

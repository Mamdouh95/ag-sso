<?php

namespace Agriserv\SSO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->has('token')) {
                $userModel = config('sso.user_model');
                $fieldMapping = config('sso.field_mapping', []);

                // Fetch user info from SSO
                $userInfo = Http::acceptJson()
                    ->contentType('application/json')
                    ->get(config('sso.base_uri') . '/api/auth/sso/user', [
                        'token' => $request->input('token')
                    ])
                    ->json();

                if (!isset($userInfo['service']['items'])) {
                    throw new \Exception("Invalid SSO response format.");
                }

                // Map SSO data to local user structure
                $userData = $this->mapUserInfo($userInfo['service']['items'], $fieldMapping);

                // Use the dynamic user model to update or create the user in the local database
                $user = $userModel::updateOrCreate([
                    'sso_id' => $userData['sso_id'] ?? $userInfo['service']['items']['id'],
                ], $userData);

                // Dynamically assign roles if provided
                if (!empty($userInfo['service']['items']['roles'])) {
                    $this->syncUserRoles($user, $userInfo['service']['items']['roles']);
                }

                // Log the user in
                \Auth::login($user);

                // Redirect after login
                return redirect()->to(session('previousUrl') ?? '/');

            } else {
                // Encrypt the redirect URI
                $encrypter = new Encrypter(config('sso.secret_key'), strtolower(config('app.cipher')));
                $token = $encrypter->encryptString(config('sso.redirect_uri'));

                // Prepare redirect URL
                $redirectUrl = config('sso.base_uri') . '?' . http_build_query([
                        'sso' => config('sso.id'),
                        'token' => $token,
                    ]);

                return redirect($redirectUrl);
            }
        } catch (\Exception $exception) {
            Log::error("SSO Login Error: " . $exception->getMessage());
            return redirect()->to('/')->withErrors(['sso_error' => 'Single sign-on failed. Please try again.']);
        }
    }

    /**
     * Map the SSO user info to local user structure based on the config mapping.
     */
    private function mapUserInfo(array $ssoData, array $fieldMapping): array
    {
        $mappedData = [];
        foreach ($fieldMapping as $localField => $ssoField) {
            $mappedData[$localField] = data_get($ssoData, $ssoField);
        }

        // Handle specific fields that might require customization
        $mappedData['phone_number'] = $ssoData['mobile_number'] ?? null;

        return $mappedData;
    }

    /**
     * Sync roles with the local user, with error handling if method not available.
     */
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

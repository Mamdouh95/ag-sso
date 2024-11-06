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
                // Get the user model from the config file
                $userModel = config('sso.user_model');

                // Fetch user info from SSO
                $userInfo = Http::acceptJson()
                    ->contentType('application/json')
                    ->get(config('sso.base_uri') . '/api/auth/sso/user?token=' . $request->input('token'))
                    ->json();

                // Modify the user info as per the original logic
                $userInfo['service']['items']['phone_number'] = $userInfo['service']['items']['mobile_number'];

                // Use the dynamic user model to update or create the user in the local database
                $user = $userModel::updateOrCreate([
                    'sso_id' => $userInfo['service']['items']['id'],
                ], $userInfo['service']['items']);

                try {
                    $user->syncRoles($userInfo['service']['items']['roles']);
                } catch (\Exception|\Throwable $e) {}

                // Log the user in
                \Auth::login($user);

                // Redirect the user after login
                return redirect()->to(session('previousUrl')) ?? redirect()->to('/');

            } else {
                // Encrypt the redirect URI using the secret key
                $encrypter = new Encrypter(config('sso.secret_key'), strtolower(config('app.cipher')));
                $token = $encrypter->encryptString(config('sso.redirect_uri'));

                // Prepare redirect URL with token and SSO ID
                $items = [
                    'redirect_away' => true,
                    'redirect_url' => config('sso.base_uri') . '?' . http_build_query([
                            'sso' => config('sso.id'),
                            'token' => $token
                        ])
                ];

                return redirect($items['redirect_url']);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}

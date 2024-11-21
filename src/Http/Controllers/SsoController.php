<?php

namespace Agriserv\SSO\Http\Controllers;

use Agriserv\SSO\Events\UserFetchedFromSso;
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

                event(new UserFetchedFromSso($userInfo));

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
}

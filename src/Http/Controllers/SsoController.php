<?php

namespace Agriserv\SSO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Encrypt the redirect URI
            $encrypter = new Encrypter(config('sso.secret_key'), strtolower(config('app.cipher')));

            $token = $encrypter->encryptString(config('sso.redirect_uri'));

            // Prepare redirect URL
            $redirectUrl = config('sso.base_uri') . '?' . http_build_query([
                    'sso' => config('sso.id'),
                    'token' => $token,
                ]);

            return redirect($redirectUrl);

        } catch (\Exception $exception) {

            Log::error("SSO Login Error: " . $exception->getMessage());

            return redirect()->to('/')->withErrors(['sso_error' => 'Single sign-on failed. Please try again.']);
        }
    }

    public function callback(Request $request)
    {
        // Fetch user info from SSO
        if (!$request->filled('token')) {
            Log::warning('SSO Callback: token missing, restarting SSO flow.');

            return redirect()->route('auth.sso');
        }

        $userInfo = Http::acceptJson()
            ->contentType('application/json')
            ->get(config('sso.base_uri') . '/api/auth/sso/user', [
                'token' => $request->input('token')
            ])
            ->json();

        if (!isset($userInfo['service']['items'])) {
            // Expired/invalid one-time token — restart the SSO flow instead of erroring,
            // but bail out after a few failures to avoid a redirect loop
            Log::warning('SSO Callback: invalid response for token exchange.');

            $retries = (int) session('ssoRetryCount', 0);

            if ($retries >= 3) {
                session()->forget('ssoRetryCount');

                return redirect()->to('/')->withErrors(['sso_error' => 'Single sign-on failed. Please try again.']);
            }

            session()->put('ssoRetryCount', $retries + 1);

            return redirect()->route('auth.sso');
        }

        session()->forget('ssoRetryCount');

        return $this->handleUserInfo($userInfo['service']['items']);
    }

    /**
     * Handle the fetched user info. This is meant to be overridden or published in the application.
     *
     * @param array $userInfo
     * @return mixed
     */
    protected function handleUserInfo(array $userInfo)
    {
        // Stub to be published in the application
        throw new \Exception("Please implement the 'handleUserInfo' method in your application.");
    }
}

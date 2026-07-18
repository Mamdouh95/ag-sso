<?php

namespace Agriserv\SSO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Encrypt the redirect URI
            $encrypter = new Encrypter(config('sso.secret_key'), strtolower(config('app.cipher')));

            $token = $encrypter->encryptString(config('sso.redirect_uri'));

            // Single-use CSRF state nonce, verified when the portal redirects back
            $state = Str::random(40);
            $request->session()->put('ssoState', $state);

            // Prepare redirect URL
            $redirectUrl = config('sso.base_uri') . '?' . http_build_query([
                    'sso' => config('sso.id'),
                    'token' => $token,
                    'state' => $state,
                ]);

            return redirect($redirectUrl);

        } catch (\Exception $exception) {

            Log::error("SSO Login Error: " . $exception->getMessage());

            return redirect()->to('/')->withErrors(['sso_error' => 'Single sign-on failed. Please try again.']);
        }
    }

    public function callback(Request $request)
    {
        if (!$request->filled('token')) {
            Log::warning('SSO Callback: token missing, restarting SSO flow.');

            return $this->restartSsoFlow();
        }

        // Reject callbacks that don't round-trip the state this session sent.
        // Portal-initiated logins carry no state; restarting converts them into
        // a normal app-initiated flow that succeeds one redirect later.
        $expectedState = $request->session()->pull('ssoState');
        $state         = $request->query('state');

        if (!is_string($expectedState) || !is_string($state) || !hash_equals($expectedState, $state)) {
            Log::warning('SSO Callback: missing or mismatched state, restarting SSO flow.');

            return $this->restartSsoFlow();
        }

        // Exchange the one-time token, authenticating as this application.
        // Credentials travel in headers so they never land in access logs.
        $userInfo = Http::acceptJson()
            ->asForm()
            ->withHeaders([
                'X-SSO-ID' => config('sso.id'),
                'X-SSO-SECRET' => config('sso.secret_key'),
            ])
            ->post(config('sso.base_uri') . '/api/auth/sso/user', [
                'token' => $request->input('token'),
            ])
            ->json();

        if (!isset($userInfo['service']['items'])) {
            // Expired/invalid one-time token — restart the SSO flow instead of erroring
            Log::warning('SSO Callback: invalid response for token exchange.');

            return $this->restartSsoFlow();
        }

        session()->forget('ssoRetryCount');

        return $this->handleUserInfo($userInfo['service']['items']);
    }

    /**
     * Restart the SSO flow, bailing out after a few attempts to avoid a redirect loop.
     *
     * @return mixed
     */
    protected function restartSsoFlow()
    {
        $retries = (int) session('ssoRetryCount', 0);

        if ($retries >= 3) {
            session()->forget('ssoRetryCount');

            return redirect()->to('/')->withErrors(['sso_error' => 'Single sign-on failed. Please try again.']);
        }

        session()->put('ssoRetryCount', $retries + 1);

        return redirect()->route('auth.sso');
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

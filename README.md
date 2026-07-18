# AgriServ SSO

Easily integrate your application with **AgriServ SSO**.

---

## v2.0

v2.0 strengthens the login flow: the callback authenticates to the SSO server
when exchanging the one-time login token, and the handshake carries a
single-use `state` value that is verified when the user returns to
`auth/callback`.

### Upgrading from v1.x

1. Upgrade the package: `composer require agriserv/sso:^2.0`.
   No changes to your published `LoginSsoController` are needed.
2. The SSO server must be running its matching v2 release.
3. After verifying login works, ask the SSO Administrator to enable
   **Require Authenticated Exchange** for your application.

> Logins started from the SSO portal's app tiles are transparently restarted
> as app-initiated logins (one extra redirect). This is expected.

---

## Installation Steps

### 1. Install the Package
Add the package to your Laravel application via Composer:

```bash
composer require agriserv/sso
```

### 2. Publish the Configuration and Controller

```bash
php artisan vendor:publish --provider="Agriserv\SSO\AgriservSsoServiceProvider" --tag="config"
```
This will create a config/sso.php file in your application.

```bash
php artisan vendor:publish --provider="Agriserv\SSO\AgriservSsoServiceProvider" --tag="sso-controller"
```
This will publish a controller to your application at:
app/Http/Controllers/LoginSsoController.php.

### 3. Set Up an Application in AgriServ SSO
Ask the AgriServ SSO Administrator to generate an application for you. Provide them with the following details:
CALL_BACK_URI: Your application's callback URL — this is always `https://your-app.example.com/auth/callback` (the `auth/callback` route registered by this package).

### 4. Configure the .env File
Add the following variables to your .env file:

```bash
SSO_BASE_URI="https://sso.agriserv.sa"
SSO_ID="APP_ID" # Replace with the Application ID provided by AgriServ Admin
SSO_SECRET_KEY="SECRET_KEY" # Replace with the Secret Key provided by AgriServ Admin
SSO_REDIRECT_URI="https://your-app.example.com/auth/callback" # Must be the auth/callback route, and must match the redirect URI registered with the AgriServ Admin exactly
SSO_LOGOUT_URL="https://sso.agriserv.sa/auth/logout" # The SSO portal's logout route
```

> **Important:** `SSO_REDIRECT_URI` must point at the package's `auth/callback` route, not your application root. If it points anywhere else the one-time login token is never exchanged and the user bounces between your app and the SSO portal.

> **Important:** `SSO_LOGOUT_URL` must point at the SSO portal's `auth/logout` route. Without it, logging out only ends your app's local session — the portal session survives and silently signs the user back in on the next protected page, making logout appear broken.

### 5. Override the handleUserInfo Method
Once the LoginSsoController is published, you can override the handleUserInfo method to handle the logic for processing user data from SSO.

Example: Handling User Info
Open the ```app/Http/Controllers/LoginSsoController.php``` file, and modify the ```handleUserInfo``` method as needed:

```bash
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
```

### 6. Test Your Integration
Test your SSO integration by navigating to the SSO login route (e.g., ```/auth/sso```). Ensure users are logged in and synchronized correctly with your application.

### 7. Add Middleware for the protected routes
```
Route::middleware('sso_auth')->group(function () {
// Add routes
}
```
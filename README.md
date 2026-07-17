# AgriServ SSO

Easily integrate your application with **AgriServ SSO**.

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
```

> **Important:** `SSO_REDIRECT_URI` must point at the package's `auth/callback` route, not your application root. If it points anywhere else the one-time login token is never exchanged and the user bounces between your app and the SSO portal.

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
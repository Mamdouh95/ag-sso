<?php

return [
    // Authentication
    'base_uri' => env('SSO_BASE_URI'),
    'id' => env('SSO_ID'),
    'secret_key' => env('SSO_SECRET_KEY'),
    'redirect_uri' => env('SSO_REDIRECT_URI'),
    // User Model
    'user_model' => env('SSO_USER_MODEL', '\App\Models\User::class'), // Default to \App\Models\User
];

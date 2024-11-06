<?php

return [
    // Authentication
    'base_uri' => env('SSO_BASE_URI'),
    'id' => env('SSO_ID'),
    'secret_key' => env('SSO_SECRET_KEY'),
    'redirect_uri' => env('SSO_REDIRECT_URI'),

    // Logout
    'logout_url' => env('SSO_LOGOUT_URL'),

    // User Model
    'user_model' => env('SSO_USER_MODEL', '\App\Models\User'), // Default to \App\Models\User

    // Field mapping between SSO data and local user data
    'field_mapping' => []
];

<?php

use App\Http\Controllers\LoginSsoController;
use Illuminate\Support\Facades\Route;
use Agriserv\SSO\Http\Controllers\SsoController;

Route::group(['middleware' => 'web'], function () {
    Route::get('auth/sso', [LoginSsoController::class, 'login'])->name('auth.sso');
    Route::get('auth/callback', [LoginSsoController::class, 'callback'])->name('auth.callback');
    Route::post('auth/logout', [SsoController::class, 'logout'])->name('auth.logout.store');
});
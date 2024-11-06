<?php

use Illuminate\Support\Facades\Route;
use Agriserv\SSO\Http\Controllers\SsoController;

Route::group(['middleware' => 'web'], function () {
    Route::get('auth/sso', [SsoController::class, 'index'])->name('frontend.auth.sso');
    Route::post('auth/logout', [SsoController::class, 'logout'])->name('frontend.auth.logout.store');
});
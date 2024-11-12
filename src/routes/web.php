<?php

use Illuminate\Support\Facades\Route;
use Agriserv\SSO\Http\Controllers\SsoController;

Route::group(['middleware' => 'web'], function () {
    Route::get('auth/sso', [SsoController::class, 'index'])->name('auth.sso');
    Route::post('auth/logout', [SsoController::class, 'logout'])->name('auth.logout.store');
});
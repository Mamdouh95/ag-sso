<?php

namespace Agriserv\SSO;

use Illuminate\Support\ServiceProvider;

class AgriservSsoServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register package services
        $this->mergeConfigFrom(
            __DIR__.'/Config/sso.php', 'sso'
        );
    }

    public function boot()
    {
        // Publish the config file
        $this->publishes([
            __DIR__ . '/Config/sso.php' => config_path('sso.php'),
        ], 'config');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
    }
}

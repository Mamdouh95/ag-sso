<?php

namespace Agriserv\SSO;

use Agriserv\SSO\Http\Middleware\SsoAuthenticate;
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

        // Publish the event
        $this->publishes([
            __DIR__.'/Stubs/Controllers/SsoController.php' => app_path('Http/Controllers/SsoController.php'),
        ], 'sso-controller');
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        // Load Middleware
        $this->app['router']->aliasMiddleware('sso_auth', SsoAuthenticate::class);
    }
}

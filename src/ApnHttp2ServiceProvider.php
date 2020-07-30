<?php

namespace Apantle\ApnHttp2Notification;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;

class ApnHttp2ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/apn_push.php' => config_path('apn_push.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/apn_push.php', 'laravel-notification-apn-http2');

        // Register the main class to use with the facade
        Notification::extend('apn', function ($app) {
            return new ApnHttp2Channel();
        });
    }
}

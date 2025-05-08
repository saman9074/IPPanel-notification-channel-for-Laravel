<?php

namespace NotificationChannels\Ippanel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Ippanel\IppanelChannel; // Import the channel class

class IppanelNotificationChannelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     * This method is called after all other service providers have been registered.
     * Here we prepare the configuration file for publishing and register the channel.
     */
    public function boot()
    {
        // Publish the package's configuration file.
        // By running `php artisan vendor:publish --tag=ippanel-config` in the main Laravel project,
        // this file will be copied to the user's config/ directory.
        $this->publishes([
            __DIR__.'/../config/ippanel.php' => config_path('ippanel.php'),
        ], 'ippanel-config'); // Publishing tag for grouping

        // Register the channel with Laravel's notification system.
        // This allows using the alias 'ippanel' in the notification's via() method.
        Notification::extend('ippanel', function ($app) {
            // You can resolve any dependencies your channel might have here.
            // In this simple example, the channel has no specific dependencies.
            return new IppanelChannel();
        });
    }

    /**
     * Register the application services.
     * This method is called when the service provider is registered.
     * Here we merge the package's configuration file with the main Laravel configuration.
     */
    public function register()
    {
        // Merge the package's configuration file with the main Laravel configuration.
        // This ensures that the package's settings are available even without publishing the config file.
        $this->mergeConfigFrom(__DIR__.'/../config/ippanel.php', 'ippanel');

        // You can also register other bindings here.
        // Example:
        // $this->app->bind(IppanelChannel::class, function ($app) {
        //     return new IppanelChannel(/* dependencies */);
        // });
    }
}
// This service provider is responsible for bootstrapping the Ippanel notification channel.
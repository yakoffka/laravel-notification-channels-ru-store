<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

use Illuminate\Support\ServiceProvider;

class RuStoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Bootstrap code here.

        // /**
        //  * Here's some example code we use for the pusher package.
        //  */
        // $this->app->when(Channel::class)
        //     ->needs(Pusher::class)
        //     ->give(function () {
        //         $pusherConfig = config('broadcasting.connections.pusher');
        //
        //         return new Pusher(
        //             $pusherConfig['key'],
        //             $pusherConfig['secret'],
        //             $pusherConfig['app_id']
        //         );
        //     });

        $this->publishes([
            __DIR__ . '/../config/ru-store.php' => config_path('ru-store.php'),
        ]);

    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ru-store.php',
            'ru-store' // Ключ конфигурации (config('ru-store'))
        );
    }
}

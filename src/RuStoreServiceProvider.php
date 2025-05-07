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
            'ru-store' // Ключ конфигурации: config('ru-store')
        );
    }
}

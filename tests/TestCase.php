<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test;

use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\RuStoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $url = 'https://vkpns.rustore.ru/*';

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $this->app['config']->set('ru-store.project_id', 'test_ru-store_project_id');
        $this->app['config']->set('ru-store.token', 'test_ru-store_token');
    }

    /**
     * @param $app
     * @return class-string[]
     */
    protected function getPackageProviders($app)
    {
        return [RuStoreServiceProvider::class];
    }
}

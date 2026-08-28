<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test;

use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\RuStoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $url = '';

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        // $this->app['config']->set('ru-store.project_id', env('RUSTORE_PROJECT_ID', 'test'));
        // $this->app['config']->set('ru-store.token', env('RUSTORE_TOKEN', 'test'));
        $this->app['config']->set('ru-store.project_id', 'Rx8IE0g5r-6-zhjWN0IixVacYM1TMI8q');
        $this->app['config']->set('ru-store.token', 'jd447ZsYdcAIy29XJSQZXEmlr8at4VWgtGka225CxSqGIo-qfI4IQx0WHWhmRguJ');
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

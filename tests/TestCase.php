<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore\Test;

use Dotenv\Dotenv;
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
        $this->setEnv();
        $this->setUrl();
    }

    /**
     * @param $app
     * @return class-string[]
     */
    protected function getPackageProviders($app)
    {
        return [RuStoreServiceProvider::class];
    }

    /**
     * Загрузка переменных из .env
     *
     * @return void
     */
    private function setEnv(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $this->app['config']->set('ru-store.project_id', env('RUSTORE_PROJECT_ID'));
        $this->app['config']->set('ru-store.token', env('RUSTORE_TOKEN'));
    }

    /**
     * Получение url для отправки push-уведомления
     *
     * @return void
     * @todo дублирование кода
     */
    private function setUrl()
    {
        $this->url = sprintf(config('ru-store.url'), config('ru-store.project_id'));
    }
}

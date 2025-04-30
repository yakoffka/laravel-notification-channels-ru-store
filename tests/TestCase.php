<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore\Test;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }
}

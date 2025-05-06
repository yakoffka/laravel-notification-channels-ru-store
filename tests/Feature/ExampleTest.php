<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\RuStore\RuStoreChannel;
use NotificationChannels\RuStore\Test\Notifications\TestNotification;
use NotificationChannels\RuStore\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * ExampleTest - пример тестового класса
 */
class ExampleTest extends TestCase
{
    #[Test]
    #[TestDox('Пример теста')]
    public function example_feature_test(): void
    {
        Notification::fake();
        $notification = new TestNotification();
        $notifiable = new AnonymousNotifiable();

        $notifiable->notify($notification);

        Notification::assertSentTo(
            $notifiable,
            TestNotification::class,
            static function ($notification, $channels) {
                return in_array(RuStoreChannel::class, $channels, true);
            }
        );
    }
}

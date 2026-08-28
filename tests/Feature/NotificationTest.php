<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Exception;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\RuStore\RuStoreChannel;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка отправки уведомлений
 */
class NotificationTest extends TestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    #[TestDox('Простая проверка отправки уведомления через канал RuStoreChannel')]
    public function notificationSimple(): void
    {
        Notification::fake();
        $notification = new TestNotification();
        $notifiable = new AnonymousNotifiable();

        $notifiable->notify($notification);

        Notification::assertSentTo(
            $notifiable,
            TestNotification::class,
            static fn($notification, $channels) => in_array(RuStoreChannel::class, $channels, true),
        );
    }
}

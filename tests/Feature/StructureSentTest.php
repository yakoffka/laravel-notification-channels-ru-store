<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Reports\RuStoreSingleReport;
use NotificationChannels\RuStore\RuStoreChannel;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка структуры отчета события NotificationSent ($event->data['report'])
 */
class StructureSentTest extends TestCase
{
    #[Test]
    #[TestDox('Проверка структуры события NotificationSent при пустом списке токенов пользователя')]
    public function structureSentOnEmptyList(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->unsetTokens();

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static fn(NotificationSent $event) => $event->channel === RuStoreChannel::class
                && $event->response->toArray() === []);
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Проверка структуры события NotificationSent при успешной отправке единственного сообщения')]
    public function structureSentOnSingle(): void
    {
        Event::fake();
        Http::fakeSequence()->push(null, 200);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) use ($notifiable) {
            /** @var RuStoreSingleReport $report */
            $report = $event->response->sole();

            return $event->channel === RuStoreChannel::class
                && $event->response->count() === 1
                && [$report->target()] === $notifiable->routeNotificationForRuStore()
                && $report->isSuccess()
                && $report->error() === null;
        });
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Проверка структуры события NotificationSent при успешной отправке нескольких сообщений')]
    public function structureSentOnMulti(): void
    {
        Event::fake();
        Http::fakeSequence()
            ->push(null, 200)
            ->push(null, 200);
        $notification = new TestNotification();
        $token_1 = 'example_ru_store_token_1';
        $token_2 = 'example_ru_store_token_2';
        $notifiable = (new TestNotifiableModel())->setTokens([$token_1, $token_2]);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) use ($notifiable, $token_1, $token_2) {
            /** @var RuStoreSingleReport $report_1 */
            $report_1 = $event->response->filter(fn(RuStoreSingleReport $report) => $report->target() === $token_1)->sole();
            /** @var RuStoreSingleReport $report_2 */
            $report_2 = $event->response->filter(fn(RuStoreSingleReport $report) => $report->target() === $token_2)->sole();

            return $event->channel === RuStoreChannel::class
                && $event->response->count() === 2
                && [$report_1->target(), $report_2->target()] === $notifiable->routeNotificationForRuStore()
                && $report_1->isSuccess()
                && $report_2->isSuccess()
                && $report_1->error() === null
                && $report_2->error() === null;
        });
        Event::assertNotDispatched(NotificationFailed::class);
    }
}

<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка поджигания событий NotificationSent и NotificationFailed
 */
class EventsFireTest extends TestCase
{
    #[Test]
    #[TestDox('Попытка отправки уведомления на пустом списке токенов - http-запросов не ожидается')]
    public function eventsFireOnEmptyListSuccess(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->unsetTokens();

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            /** @var Collection $response */
            $response = $event->response;
            return $response->toArray() === [];
        });
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Успешная отправка уведомления на одно устройство. NotificationFailed не поджигается')]
    public function eventsFireOnOnlyOneSuccess(): void
    {
        Event::fake();
        Http::fakeSequence()->push(null, 200);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens(['example_ru_store_token']);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            /** @var RuStoreReport $report */
            $report = $event->response->sole();
            return $report->target() === 'example_ru_store_token';
        });
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Ошибочная отправка уведомления на одно устройство. NotificationSent поджигается, но response->reports пуст')]
    public function eventsFireOnOnlyOneFail(): void
    {
        Event::fake();
        Http::fakeSequence()->push([
            'error' => [
                'code' => 404,
                'message' => 'Requested entity was not found.',
                'status' => 'NOT_FOUND',
            ],
        ], 404);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens(['invalid_fcm_token']);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            /** @var Collection $response */
            $response = $event->response;
            return $response->toArray() === [];
        });
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];
            return $report->target() === 'invalid_fcm_token';
        });
    }

    #[Test]
    #[TestDox('Отправка уведомления на два устройства: отправка на первое вернула 200, на второе - 404')]
    public function eventsFireOnOneSuccessOneFail(): void
    {
        Event::fake();
        Http::fakeSequence()
            ->push(null, 200)
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens(['example_ru_store_token', 'invalid_ru_store_token']);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            /** @var RuStoreReport $report */
            $report = $event->response->sole();
            return $report->target() === 'example_ru_store_token';

        });
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];
            return $report->target() === 'invalid_ru_store_token';
        });
    }

    #[Test]
    #[TestDox('Отправка уведомления на четыре устройства: на два удачно, на два - неудачно')]
    public function eventsFireOnTwoSuccessTwoFail(): void
    {
        Event::fake();
        Http::fakeSequence()
            ->push(null, 200)
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404)
            ->push(null, 200)
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens(['1_valid', '2_invalid', '3_valid', '4_invalid']);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            $tokens = $event->response
                ->map(fn(RuStoreReport $report) => $report->target())
                ->toArray();
            return $tokens === ['1_valid', '3_valid'];
        });
        Event::assertDispatchedTimes(NotificationFailed::class, 2);
        Event::assertDispatched(static function (NotificationFailed $event) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];
            return $report->target() === '2_invalid';
        });
        Event::assertDispatched(static function (NotificationFailed $event) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];
            return $report->target() === '4_invalid';
        });
    }
}

<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\RuStorePushNotingSentException;
use NotificationChannels\RuStore\Reports\RuStoreSingleReport;
use NotificationChannels\RuStore\Test\Notifiable\User;
use NotificationChannels\RuStore\Test\Notifications\TestNotification;
use NotificationChannels\RuStore\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EventsFireTest - проверка поджигания событий NotificationSent и NotificationFailed
 */
class EventsFireTest extends TestCase
{
    #[Test]
    #[TestDox('Попытка отправки уведомления на пустом списке токенов - http-запросов не ожидается')]
    public function eventsFireOnEmptyListSuccess(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new User())->unsetTokens();

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            /** @var Collection $response */
            $response = $event->response;
            return $response->toArray() === [];
        });
        // Event::assertDispatched(static fn(NotificationSent $event) => $event->response->all() === []
        //     && $event->notifiable === $notifiable);
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Успешная отправка уведомления на одно устройство. NotificationFailed не поджигается')]
    public function eventsFireOnOnlyOneSuccess(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new User())->setTokens(['example_ru_store_token']);
        Http::fakeSequence()->push(null, 201);

        $notifiable->notify($notification);

        // Event::assertDispatched(static function (NotificationSent $event) {
        //     return $event->response->keys()->toArray() === ['example_ru_store_token'];
        // });
        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            // dd($event); @todo дополнить проверки!
            /** @var RuStoreSingleReport $report */
            $report = $event->response->sole();
            return $report->target() === 'example_ru_store_token';
        });
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Ошибочная отправка уведомления на одно устройство. NotificationSent поджигается, но response->reports пуст')]
    // @todo дополнить всеми возможными типами ошибок (сервер, соединение, клиентские[исключение при получении токенов?])
    public function eventsFireOnOnlyOneFail(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new User())->setTokens(['invalid_fcm_token']);
        Http::fakeSequence()->push([
            'error' => [
                'code' => 404,
                'message' => 'Requested entity was not found.',
                'status' => 'NOT_FOUND',
            ],
        ], 404);

        try {
            $notifiable->notify($notification);
        } catch (RuStorePushNotingSentException $e) {
        }

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            /** @var Collection $response */
            $response = $event->response;
            return $response->toArray() === [];
        });
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) {
            // dd($event); @todo дополнить проверки!
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            return $report->target() === 'invalid_fcm_token';
        });
    }

    #[Test]
    #[TestDox('Отправка уведомления на два устройства: отправка на первое вернула 200, на второе - 404')]
    public function eventsFireOnOneSuccessOneFail(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new User())->setTokens(['example_ru_store_token', 'invalid_ru_store_token']);
        Http::fakeSequence()
            ->push(null, 200)
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            // dd($event); @todo дополнить проверки!
            /** @var RuStoreSingleReport $report */
            $report = $event->response->sole();
            return $report->target() === 'example_ru_store_token';

        });
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) {
            // dd($event); @todo дополнить проверки!
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            return $report->target() === 'invalid_ru_store_token';
        });
    }

    #[Test]
    #[TestDox('Отправка уведомления на четыре устройства: на два удачно, на два - неудачно')]
    public function eventsFireOnTwoSuccessTwoFail(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new User())->setTokens(['1_valid', '2_invalid', '3_valid', '4_invalid']);
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

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) {
            $tokens = $event->response
                ->map(fn(RuStoreSingleReport $report) => $report->target())
                ->toArray();
            return $tokens === ['1_valid', '3_valid'];
        });
        Event::assertDispatchedTimes(NotificationFailed::class, 2);
        Event::assertDispatched(static function (NotificationFailed $event) {
            // dd($event); @todo дополнить проверки!
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            return $report->target() === '2_invalid';
        });
        Event::assertDispatched(static function (NotificationFailed $event) {
            // dd($event); @todo дополнить проверки!
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            return $report->target() === '4_invalid';
        });
    }
}

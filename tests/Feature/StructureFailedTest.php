<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\RuStoreChannel;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка структуры отчета события NotificationFailed ($event->data['report'])
 */
class StructureFailedTest extends TestCase
{
    #[Test]
    #[TestDox('Проверка структуры события NotificationFailed при ошибочной отправке единственного сообщения')]
    public function structureFailedOnSingle(): void
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
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];

            return $event->channel === RuStoreChannel::class
                && [$report->target()] === $notifiable->routeNotificationForRuStore()
                && $report->isFailure()
                && $report->error()->getCode() === 404;
        });
    }

    #[Test]
    #[TestDox('Проверка структуры события NotificationFailed при ошибочной отправке нескольких сообщений')]
    public function structureFailedOnMulti(): void
    {
        Event::fake();
        Http::fakeSequence()
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404)
            ->push([
                'error' => [
                    'code' => 400,
                    'message' => 'The registration token is not a valid FCM registration token',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ], 400);
        $notification = new TestNotification();
        $token_1 = 'example_ru_store_token_1';
        $token_2 = 'example_ru_store_token_2';
        $notifiable = (new TestNotifiableModel())->setTokens([$token_1, $token_2]);

        $notifiable->notify($notification);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatchedTimes(NotificationFailed::class, 2);
        Event::assertDispatched(static function (NotificationFailed $event) use ($token_1) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];

            return $event->channel === RuStoreChannel::class
                && $report->target() === $token_1
                && $report->isFailure()
                && $report->error()->getCode() === 404;
        });
        Event::assertDispatched(static function (NotificationFailed $event) use ($token_2) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];

            return $event->channel === RuStoreChannel::class
                && $report->target() === $token_2
                && $report->isFailure()
                && $report->error()->getCode() === 400;
        });
    }
}

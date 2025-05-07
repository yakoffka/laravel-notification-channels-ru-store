<?php
declare(strict_types=1);

namespace Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\CouldNotSendNotification;
use NotificationChannels\RuStore\Test\Notifiable\User;
use NotificationChannels\RuStore\Test\Notifications\TestNotification;
use NotificationChannels\RuStore\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * SeveralTokensTest - проверка отправки уведомлений на несколько устройств пользователя
 */
class SeveralTokensTest extends TestCase
{
    #[Test]
    #[TestDox('Отправка уведомления на два устройства: отправка на первое вернула 200, на второе - 404')]
    public function severalTokensOneSuccessOneFail(): void
    {
        //Http::fake();
        Event::fake();
        $notification = new TestNotification();
        $notifiable = (new User())->setTokens(['valid', 'invalid']);
        // Http::sequence([$this->url => Http::response()]);
        // Http::fake([$this->url => Http::sequence()
        //     ->push(Http::response())
        //     ->push(Http::response([
        //         'error' => [
        //             'code' => 404,
        //             'message' => 'Requested entity was not found.',
        //             'status' => 'NOT_FOUND',
        //         ]
        //     ], 404))]);
//        Http::sequence([
//            //Http::response(),
//            Http::response([
//                'error' => [
//                    'code' => 404,
//                    'message' => 'Requested entity was not found.',
//                    'status' => 'NOT_FOUND',
//                ]
//            ], 404),
//            Http::response([
//                'error' => [
//                    'code' => 404,
//                    'message' => 'Requested entity was not found.',
//                    'status' => 'NOT_FOUND',
//                ]
//            ], 404),
//        ]);
        Http::fakeSequence()
            ->push(null, 200)
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ]
            ], 404);

        $notifiable->notify($notification);

        Event::assertDispatched(function (NotificationSent $event) {
            dd($event);
            // return $event->order->id === $order->id;
        });
        Event::assertDispatched(function (NotificationFailed $event) {
            dd($event);
            // return $event->order->id === $order->id;
        });
        // Event::assertDispatched(NotificationSending::class);
        // Event::assertDispatched(NotificationSent::class);
    }

    #[Test]
    #[TestDox('Отправка уведомления на четыре устройства: на два удачно, на два - неудачно')]
    public function severalTokensTwoSuccessTwoFail(): void
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
                ]
            ], 404)
            ->push(null, 200)
            ->push([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ]
            ], 404);

        $notifiable->notify($notification);

//        Event::assertDispatched(function (NotificationSent $event) {
//            dd($event);
//            // return $event->order->id === $order->id;
//        });
        Event::assertDispatched(function (NotificationFailed $event) {
            dd($event);
            // return $event->order->id === $order->id;
        });
        // Event::assertDispatched(NotificationSending::class);
        // Event::assertDispatched(NotificationSent::class);
    }

//    #[Test]
//    #[TestDox('Проверка обработки ошибочного ответа 404')]
//    public function handle_error_response404(): void
//    {
//        $notification = new TestNotification();
//        $notifiable = new User();
//        Http::fake([
//            $this->url => Http::response([
//                'error' => [
//                    'code' => 404,
//                    'message' => 'Requested entity was not found.',
//                    'status' => 'NOT_FOUND',
//                ]
//            ], 404),
//        ]);
//        $this->expectException(CouldNotSendNotification::class);
//        $this->expectExceptionMessage('The communication with RuStore failed. "HTTP request returned status code 404:'
//            . "\n" . '{"error":{"code":404,"message":"Requested entity was not found.","status":"NOT_FOUND"}}' . "\n" . '"');
//
//        $notifiable->notify($notification);
//        //Event::assertDispatched(NotificationFailed::class);
//    }
}

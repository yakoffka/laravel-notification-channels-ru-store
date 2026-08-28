<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Reports\RuStoreSingleReport;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка обработки основных статусов ответа
 * @todo убрать дублирование с тестами ExpectedErrorTest!
 */
class StatusCodeTest extends TestCase
{
    #[Test]
    #[TestDox('Проверка обработки успешного ответа')]
    public function statusCode200Simple(): void
    {
        Event::fake();
        Http::fake([$this->url => Http::response()]);
        $notification = new TestNotification();
        $notifiable = new TestNotifiableModel();

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Проверка обработки ошибочного ответа 301 Moved Permanently')]
    public function statusCodeUndocumented301(): void
    {
        Event::fake();
        Http::fake([
            $this->url => Http::response([
                'code' => 301,
                'message' => 'Moved Permanently',
                'status' => '',
            ], 301),
        ]);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable) {
            // /** @var RequestException $e */
            // $e = $event->data['report']->all()->sole()->error();
            // return $e->getCode() === 301
            //     && $e->getMessage() === 'RuStoreRedirect: {"code":301,"message":"Moved Permanently","status":""}';
            // dd($event); @todo дополнить проверки! getCode() getMessage()
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            return [$report->target()] === $notifiable->routeNotificationForRuStore();
        });
        // Event::assertDispatched(static function (NotificationFailed $event) {
        //     if (isset($event->data['report'])) {
        //         /** @var RequestException $e */
        //         $e = $event->data['report']->all()->sole()->error();
        //         return $e->getCode() === 301
        //             && $e->getMessage() === 'RuStoreRedirect: {"code":301,"message":"Moved Permanently","status":""}';
        //     }
        //
        //     if (isset($event->data['exception'])) {
        //         $e = $event->data['exception'];
        //         return $e::class === RuStorePushNotingSentException::class;
        //     }
        //
        //     return false;
        // });
    }

    #[Test]
    #[TestDox('Проверка обработки ответа на запрос с невалидным push-токеном 400|INVALID_ARGUMENT')]
    public function statusCode400InvalidArgument(): void
    {
        Event::fake();
        $body = [
            'error' => [
                'code' => 400,
                'message' => 'The registration token is not a valid FCM registration token',
                'status' => 'UNAUTHORIZED',
            ],
        ];
        Http::fake([$this->url => Http::response($body, 400)]);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable, $body) {
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            // dd($report->response()->body());
            return [$report->target()] === $notifiable->routeNotificationForRuStore()
                && $report->response()::class === Response::class
                && $report->isFailure()
                && $report->error()->getCode() === 400 // @todo исправить?
                // && $report->error()->getMessage() ===  // @todo исправить?
                && $report->response()->getStatusCode() === 400
                && $report->response()->body() === json_encode($body, JSON_THROW_ON_ERROR);
        });
    }

    #[Test]
    #[TestDox('Проверка обработки ошибочного ответа 401 UNAUTHORIZED')]
    public function statusCode401Unauthorized(): void
    {
        Event::fake();
        $body = [
            'error' => [
                'code' => 401,
                'message' => 'The registration token is not a valid FCM registration token',
                'status' => 'UNAUTHORIZED',
            ],
        ];
        Http::fake([$this->url => Http::response($body, 401)]);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable, $body) {
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];
            // dd($report);
            return [$report->target()] === $notifiable->routeNotificationForRuStore()
                && $report->response()::class === Response::class
                && $report->isFailure()
                && $report->error()->getCode() === 401 // @todo исправить?
                // && $report->error()->getMessage() ===  // @todo исправить?
                && $report->response()->getStatusCode() === 401
                && $report->response()->body() === json_encode($body, JSON_THROW_ON_ERROR);
        });
    }

    //    #[Test]
    //    #[TestDox('Проверка обработки ошибочного ответа 403 Forbidden')]
    //    public function handle_error_response403(): void
    //    {
    //        Event::fake();
    //        Http::fake([
    //            $this->url => Http::response([
    //                'error' => [
    //                    'code' => 403,
    //                    'message' => 'SenderId mismatch',
    //                    'status' => 'PERMISSION_DENIED',
    //                ],
    //            ], 403),
    //        ]);
    //        $notification = new TestNotification();
    //        $notifiable = (new TestNotifiableModel())->setTokens();
    //
    //        $notifiable->notify($notification);
    //
    //        Event::assertDispatched(NotificationSending::class);
    //        Event::assertDispatchedTimes(NotificationFailed::class, 1);
    //        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable) {
    //            // /** @var RequestException $e */
    //            // $e = $event->data['report']->all()->sole()->error();
    //            // return $e->getCode() === 301
    //            //     && $e->getMessage() === 'RuStoreRedirect: {"code":301,"message":"Moved Permanently","status":""}';
    //            // dd($event); @todo дополнить проверки! getCode() getMessage()
    //            /** @var RuStoreSingleReport $report */
    //            $report = $event->data['report'];
    //            return [$report->target()] === $notifiable->routeNotificationForRuStore();
    //        });
    //        //        Event::assertDispatched(static function (NotificationFailed $event) {
    //        //            if (isset($event->data['report'])) {
    //        //                /** @var RequestException $e */
    //        //                $e = $event->data['report']->all()->sole()->error();
    //        //                return $e->getCode() === 403 && $e->getMessage() === 'RuStoreClientError: '
    //        //                    . '{"error":{"code":403,"message":"SenderId mismatch","status":"PERMISSION_DENIED"}}';
    //        //            }
    //        //
    //        //            if (isset($event->data['exception'])) {
    //        //                $e = $event->data['exception'];
    //        //                return $e::class === RuStorePushNotingSentException::class;
    //        //            }
    //        //
    //        //            return false;
    //        //        });
    //    }
    //
    //    #[Test]
    //    #[TestDox('Проверка обработки ошибочного ответа 404')]
    //    public function handle_error_response404(): void
    //    {
    //        Event::fake();
    //        Http::fake([
    //            $this->url => Http::response([
    //                'error' => [
    //                    'code' => 404,
    //                    'message' => 'Requested entity was not found.',
    //                    'status' => 'NOT_FOUND',
    //                ],
    //            ], 404),
    //        ]);
    //        $notification = new TestNotification();
    //        $notifiable = (new TestNotifiableModel())->setTokens();
    //
    //        $notifiable->notify($notification);
    //
    //        Event::assertDispatched(NotificationSending::class);
    //        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable) {
    //            // /** @var RequestException $e */
    //            // $e = $event->data['report']->all()->sole()->error();
    //            // return $e->getCode() === 301
    //            //     && $e->getMessage() === 'RuStoreRedirect: {"code":301,"message":"Moved Permanently","status":""}';
    //            // dd($event); @todo дополнить проверки! getCode() getMessage()
    //            /** @var RuStoreSingleReport $report */
    //            $report = $event->data['report'];
    //            return [$report->target()] === $notifiable->routeNotificationForRuStore();
    //        });
    //        //        Event::assertDispatched(static function (NotificationFailed $event) {
    //        //            if (isset($event->data['report'])) {
    //        //                /** @var RequestException $e */
    //        //                $e = $event->data['report']->all()->sole()->error();
    //        //                return $e->getCode() === 404 && $e->getMessage() === 'RuStoreClientError: '
    //        //                    . '{"error":{"code":404,"message":"Requested entity was not found.","status":"NOT_FOUND"}}';
    //        //            }
    //        //
    //        //            if (isset($event->data['exception'])) {
    //        //                $e = $event->data['exception'];
    //        //                return $e::class === RuStorePushNotingSentException::class;
    //        //            }
    //        //
    //        //            return false;
    //        //        });
    //    }
    //
    //    #[Test]
    //    #[TestDox('Проверка обработки ошибочного ответа 500 Internal Server Error')]
    //    public function handle_error_response500(): void
    //    {
    //        Event::fake();
    //        Http::fake([
    //            $this->url => Http::response([
    //                'code' => 500,
    //                'message' => 'Internal Server Error',
    //                'status' => '',
    //            ], 500),
    //        ]);
    //        $notification = new TestNotification();
    //        $notifiable = (new TestNotifiableModel())->setTokens();
    //
    //        $notifiable->notify($notification);
    //
    //        Event::assertDispatched(NotificationSending::class);
    //        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable) {
    //            // /** @var RequestException $e */
    //            // $e = $event->data['report']->all()->sole()->error();
    //            // return $e->getCode() === 301
    //            //     && $e->getMessage() === 'RuStoreRedirect: {"code":301,"message":"Moved Permanently","status":""}';
    //            // dd($event); @todo дополнить проверки! getCode() getMessage()
    //            /** @var RuStoreSingleReport $report */
    //            $report = $event->data['report'];
    //            return [$report->target()] === $notifiable->routeNotificationForRuStore();
    //        });
    //        //        Event::assertDispatched(static function (NotificationFailed $event) {
    //        //            if (isset($event->data['report'])) {
    //        //                /** @var RequestException $e */
    //        //                $e = $event->data['report']->all()->sole()->error();
    //        //                return $e->getCode() === 500
    //        //                    && $e->getMessage() === 'RuStoreServerError: {"code":500,"message":"Internal Server Error","status":""}';
    //        //            }
    //        //
    //        //            if (isset($event->data['exception'])) {
    //        //                $e = $event->data['exception'];
    //        //                return $e::class === RuStorePushNotingSentException::class;
    //        //            }
    //        //
    //        //            return false;
    //        //        });
    //    }
}

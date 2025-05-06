<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

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
 * StatusCodeTest - проверка различных статусов ответа с помощью Http::fake()
 */
class StatusCodeTest extends TestCase
{
    #[Test]
    #[TestDox('Проверка обработки успешного ответа')]
    public function handle_success_response200(): void
    {
        Event::fake();
        $notification = new TestNotification();
        $notifiable = new User();
        Http::fake([$this->url => Http::response()]);

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
    }

    #[Test]
    #[TestDox('Проверка обработки ошибочного ответа 403 Forbidden')]
    public function handle_error_response403(): void
    {
        $notification = new TestNotification();
        $notifiable = new User();
        Http::fake([
            $this->url => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'SenderId mismatch',
                    'status' => 'PERMISSION_DENIED',
                ]
            ], 403),
        ]);
        $this->expectException(CouldNotSendNotification::class);
        $this->expectExceptionMessage('The communication with RuStore failed. "HTTP request returned status code 403:'
            . "\n" . '{"error":{"code":403,"message":"SenderId mismatch","status":"PERMISSION_DENIED"}}' . "\n" . '"');

        $notifiable->notify($notification);
        //Event::assertDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Проверка обработки ошибочного ответа 403 Forbidden')]
    public function handle_error_response401(): void
    {
        $notification = new TestNotification();
        $notifiable = new User();
        Http::fake([
            $this->url => Http::response([
                'code' => 401,
                'message' => 'unauthorized: Invalid Authorization header',
                'status' => 'UNAUTHORIZED',
            ], 401),
        ]);
        $this->expectException(CouldNotSendNotification::class);
        $this->expectExceptionMessage('The communication with RuStore failed. "HTTP request returned status code 401:'
            . "\n" . '{"code":401,"message":"unauthorized: Invalid Authorization header","status":"UNAUTHORIZED"}' . "\n" . '"');

        $notifiable->notify($notification);
        //Event::assertDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Проверка обработки ошибочного ответа 404')]
    public function handle_error_response404(): void
    {
        $notification = new TestNotification();
        $notifiable = new User();
        Http::fake([
            $this->url => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ]
            ], 404),
        ]);
        $this->expectException(CouldNotSendNotification::class);
        $this->expectExceptionMessage('The communication with RuStore failed. "HTTP request returned status code 404:'
            . "\n" . '{"error":{"code":404,"message":"Requested entity was not found.","status":"NOT_FOUND"}}' . "\n" . '"');

        $notifiable->notify($notification);
        //Event::assertDispatched(NotificationFailed::class);
    }
}

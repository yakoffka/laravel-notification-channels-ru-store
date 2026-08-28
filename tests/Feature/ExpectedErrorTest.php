<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\InvalidArgumentException;
use NotificationChannels\RuStore\Exceptions\NotFoundException;
use NotificationChannels\RuStore\Exceptions\PermissionDeniedException;
use NotificationChannels\RuStore\Exceptions\RuStoreInternalException;
use NotificationChannels\RuStore\Exceptions\TooManyRequestsException;
use NotificationChannels\RuStore\Exceptions\UnexpectedException;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка обработки описанных в документации ошибок
 * src: https://www.rustore.ru/help/sdk/push-notifications/send-push-notifications
 */
class ExpectedErrorTest extends TestCase
{
    /**
     * Ожидаемые ошибочные ответы
     * src: https://www.rustore.ru/help/sdk/push-notifications/send-push-notifications
     *
     * @return array<int, array<int, string|int>>
     */
    public static function expectedErrorProvider(): array
    {
        return [
            [
                400,
                'The registration token is not a valid FCM registration token',
                'INVALID_ARGUMENT',
                InvalidArgumentException::class,
            ],
            [
                500, // undefined
                '_undefined_',
                'INTERNAL',
                RuStoreInternalException::class,
            ],
            [
                429, // undefined
                '_undefined_',
                'TOO_MANY_REQUESTS',
                TooManyRequestsException::class,
            ],
            [
                403, // undefined
                '_undefined_',
                'PERMISSION_DENIED',
                PermissionDeniedException::class,
            ],
            [
                404,
                'Requested entity was not found.',
                'NOT_FOUND',
                NotFoundException::class,
            ],
            [
                599,
                'Неописанный статус, но структура ответа соответствует заявленной',
                'UNDEFINED_STATUS',
                UnexpectedException::class,
            ],
        ];
    }

    #[Test]
    #[TestDox('Проверка обработки ответа на запрос с невалидным push-токеном 400|INVALID_ARGUMENT')]
    #[DataProvider('expectedErrorProvider')]
    public function expectedError(int $code, string $message, string $status, string $e_class): void
    {
        Event::fake();
        $body = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'status' => $status,
            ],
        ];
        Http::fake([$this->url => Http::response($body, $code)]);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable, $code, $message, $body, $e_class) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];

            return [$report->target()] === $notifiable->routeNotificationForRuStore()
                && $report->isFailure()
                && $report->error()::class === $e_class
                && $report->error()->getCode() === $code
                && $report->error()->getMessage() === ($e_class === UnexpectedException::class
                    ? json_encode($body, JSON_THROW_ON_ERROR)
                    : $message)
                && $report->response()->getStatusCode() === $code
                && $report->response()->body() === json_encode($body, JSON_THROW_ON_ERROR);
        });
    }
}

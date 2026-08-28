<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\UnexpectedException;
use NotificationChannels\RuStore\Reports\RuStoreSingleReport;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка обработки основных статусов ответа с неожиданным телом ответа
 */
class UnexpectedResponseTest extends TestCase
{
    /**
     * Неожиданные коды ответов (400 и 500 ожидаемы, но должны содержать тело ответа с элементом 'error.status')
     *
     * @return array<int, int>
     */
    public static function unexpectedStatusCodeProvider(): array
    {
        return [
            [100],
            [300],
            [400],
            [500],
        ];
    }

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
    #[TestDox('Проверка обработки ответа на запрос с невалидным push-токеном 400|INVALID_ARGUMENT')]
    #[DataProvider('unexpectedStatusCodeProvider')]
    public function statusCodeNot200(int $code): void
    {
        Event::fake();
        $body = ['random_key' => 'random_value'];
        Http::fake([$this->url => Http::response($body, $code)]);
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens();

        $notifiable->notify($notification);

        Event::assertDispatched(NotificationSending::class);
        Event::assertDispatched(NotificationSent::class);
        Event::assertDispatched(static function (NotificationFailed $event) use ($notifiable, $code, $body) {
            /** @var RuStoreSingleReport $report */
            $report = $event->data['report'];

            return [$report->target()] === $notifiable->routeNotificationForRuStore()
                && $report->isFailure()
                && $report->error()::class === UnexpectedException::class
                && $report->error()->getCode() === $code
                && $report->error()->getMessage() === json_encode($body, JSON_THROW_ON_ERROR)
                && $report->response()->getStatusCode() === $code
                && $report->response()->body() === json_encode($body, JSON_THROW_ON_ERROR);
        });
    }
}

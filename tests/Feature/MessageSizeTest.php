<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\MessageTooLargeException;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\RuStoreMessage;
use NotificationChannels\RuStore\RuStoreMessageValidator;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Проверка ограничения RuStore на максимальный объем сообщения.
 */
class MessageSizeTest extends TestCase
{
    #[Test]
    #[TestDox('Сообщение ровно 4096 байт отправляется в RuStore')]
    public function messageWithMaxAllowedSizeIsSent(): void
    {
        Event::fake();
        Http::fakeSequence()->push(null, 200);
        $notifiable = (new TestNotifiableModel())->setTokens();
        $token = $notifiable->routeNotificationForRuStore()[0];
        $payload = new RuStoreMessage(data: ['body' => '']);
        $bodyBytes = RuStoreMessageValidator::MAX_MESSAGE_BYTES - mb_strlen($payload->getPayload($token), '8bit');
        $notification = new class ($bodyBytes) extends TestNotification {
            public function __construct(private readonly int $bodyBytes) {}

            public function toRuStore($notifiable): RuStoreMessage
            {
                return new RuStoreMessage(data: ['body' => str_repeat('a', $this->bodyBytes)]);
            }
        };

        $notifiable->notify($notification);

        $this->assertEquals(
            RuStoreMessageValidator::MAX_MESSAGE_BYTES,
            mb_strlen($notification->toRuStore($notifiable)->getPayload($token), '8bit'),
        );
        Http::assertSent(static fn($request) => mb_strlen($request->body(), '8bit') === RuStoreMessageValidator::MAX_MESSAGE_BYTES);
        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static function (NotificationSent $event) use ($token) {
            /** @var RuStoreReport $report */
            $report = $event->response->sole();

            return $report->isSuccess()
                && $report->target() === $token
                && $report->error() === null;
        });
        Event::assertNotDispatched(NotificationFailed::class);
    }

    #[Test]
    #[TestDox('Сообщение больше 4096 байт не отправляется в RuStore')]
    public function oversizedMessageIsNotSent(): void
    {
        Event::fake();
        Http::fake();
        $notifiable = (new TestNotifiableModel())->setTokens();
        $token = $notifiable->routeNotificationForRuStore()[0];
        $payload = new RuStoreMessage(data: ['body' => '']);
        $bodyBytes = RuStoreMessageValidator::MAX_MESSAGE_BYTES - mb_strlen($payload->getPayload($token), '8bit') + 1;
        $notification = new class ($bodyBytes) extends TestNotification {
            public function __construct(private readonly int $bodyBytes) {}

            public function toRuStore($notifiable): RuStoreMessage
            {
                return new RuStoreMessage(data: ['body' => str_repeat('a', $this->bodyBytes)]);
            }
        };

        $notifiable->notify($notification);

        $this->assertEquals(
            RuStoreMessageValidator::MAX_MESSAGE_BYTES + 1,
            mb_strlen($notification->toRuStore($notifiable)->getPayload($token), '8bit'),
        );
        Http::assertNothingSent();
        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static fn(NotificationSent $event) => $event->response->isEmpty());
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];

            return $report->isFailure()
                && $report->response() === null
                && $report->error() instanceof MessageTooLargeException
                && $report->error()->actualBytes() > RuStoreMessageValidator::MAX_MESSAGE_BYTES
                && $report->error()->maxBytes() === RuStoreMessageValidator::MAX_MESSAGE_BYTES;
        });
    }
}

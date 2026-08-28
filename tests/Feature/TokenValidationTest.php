<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Feature;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\InvalidPushTokenException;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\RuStoreMessageValidator;
use NotificationChannels\RuStore\Test\TestCase;
use NotificationChannels\RuStore\Test\TestNotifiableModel;
use NotificationChannels\RuStore\Test\TestNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;

/**
 * Проверка валидации ru-store-push токенов.
 */
class TokenValidationTest extends TestCase
{
    /**
     * Некорректные токены
     *
     * @return array<int, int>
     */
    public static function invalidTokenProvider(): array
    {
        return [
            [
                100500,
                'Invalid RuStore push token: expected string, got int',
            ],
            [
                '',
                'Invalid RuStore push token: token is empty',
            ],
            [
                '   ',
                'Invalid RuStore push token: token is empty',
            ],
            [
                str_repeat('a', RuStoreMessageValidator::MAX_PUSH_TOKEN_BYTES + 1),
                'Invalid RuStore push token: token size is 65 bytes, maximum allowed is 64 bytes',
            ],
            [
                [],
                'Invalid RuStore push token: expected string, got array',
            ],
            [
                new stdClass(),
                'Invalid RuStore push token: expected string, got stdClass',
            ],
        ];
    }

    #[Test]
    #[TestDox('Некорректные токены не отправляются в RuStore и попадают в NotificationFailed')]
    #[DataProvider('invalidTokenProvider')]
    public function invalidTokensAreNotSent(mixed $invalid_token, string $reason): void
    {
        Event::fake();
        Http::fake();
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens([$invalid_token]);

        $notifiable->notify($notification);

        Http::assertNothingSent();
        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static fn(NotificationSent $event) => $event->response->isEmpty());
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
        Event::assertDispatched(static function (NotificationFailed $event) use ($invalid_token, $reason) {
            /** @var RuStoreReport $report */
            $report = $event->data['report'];

            return $report->target() === (is_string($invalid_token) ? $invalid_token : '')
                && $report->error()::class === InvalidPushTokenException::class
                && $report->error()->getMessage() === $reason;
        });
    }

    #[Test]
    #[TestDox('Валидные токены отправляются, даже если рядом есть некорректные токены')]
    public function validTokensAreSentWhenInvalidTokensExist(): void
    {
        Event::fake();
        Http::fakeSequence()->push(null, 200);
        $validToken = 'valid_ru_store_token';
        $notification = new TestNotification();
        $notifiable = (new TestNotifiableModel())->setTokens(['', $validToken]);

        $notifiable->notify($notification);

        Http::assertSentCount(1);
        Http::assertSent(static fn($request) => str_contains($request->body(), $validToken));
        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatched(static fn(NotificationSent $event) => $event->response->sole()->target() === $validToken);
        Event::assertDispatchedTimes(NotificationFailed::class, 1);
    }
}

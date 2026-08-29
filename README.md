Please see [this repo](https://github.com/laravel-notification-channels/channels) for instructions on how to submit a
channel proposal.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This package makes it easy to send notifications using [RuStore](link to service) with Laravel .

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Setting up the RuStore service](#setting-up-the-RuStore-service)
- [Usage](#usage)
- [Available Message methods](#available-message-methods)
- [Changelog](#changelog)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Requirements

- PHP >= 8.2
- Laravel / Illuminate >= 11.0

## Installation

Установите пакет с помощью команды:

```bash
  composer require yakoffka/laravel-notification-channels-ru-store
```

Опубликуйте конфигурационный файл:

```bash
  php artisan vendor:publish --provider="NotificationChannels\RuStore\RuStoreServiceProvider"
```

Обновите ваш .env, указав там значения, полученные в [RuStore консоли](https://console.rustore.ru/waiting)

## Usage

В классе, использующим трейт Notifiable (например User), необходимо реализовать метод, возвращающий массив токенов
уведомляемого пользователя:

```php

    /**
     * Получение массива ru-store push-токенов пользователя.
     * Используется пакетом yakoffka/laravel-notification-channels-ru-store (laravel-notification-channels/rustore)
     *
     * @return array<int|string, mixed>
     */
    public function routeNotificationForRuStore(): array
    {
        return $this->rustore_push_tokens;
    }
```

Затем создать класс уведомления, в методе via() которого указать канал отправки RuStoreChannel и добавить метод
toRuStore():

```php
<?php
declare(strict_types=1);

namespace App\Notifications\Development;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\RuStore\Resources\MessageAndroid;
use NotificationChannels\RuStore\Resources\MessageAndroidNotification;
use NotificationChannels\RuStore\Resources\MessageNotification;
use NotificationChannels\RuStore\RuStoreChannel;
use NotificationChannels\RuStore\RuStoreMessage;

/**
 * Уведомление пользователя, отправляемое через консоль для проверки работы канала RuStore
 */
class RuStoreTestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public readonly string $title, public readonly string $body)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param User $notifiable
     * @return array
     */
    public function via(User $notifiable): array
    {
        return [
            RuStoreChannel::class, // указать канал отправки RuStoreChannel
        ];
    }

    /**
     * Формирование сообщения, отправляемого через RuStoreChannel
     *
     * @param User $notifiable
     * @return RuStoreMessage
     */
    public function toRuStore(User $notifiable): RuStoreMessage
    {
        return (new RuStoreMessage(
            notification: new MessageNotification(
                title: 'Test Push by RuStore',
                body: 'Hello! Test body from RuStoreTestingNotification',
            ),
            android: new MessageAndroid(
                notification: new MessageAndroidNotification(
                    title: 'Android test Push by RuStore',
                    body: 'Hello! Android test body from RuStoreTestingNotification',
                )
            )
        ));
    }
}

```

#### Проверка отправки уведомлений

Для контроля отправляемых уведомлений необходимо воспользоваться событиями, поджигаемыми после отправки:

- cобытие NotificationSent содержит коллекцию отчетов RuStoreReport в свойстве response:
  ```$report = $event->response;```
- cобытие NotificationFailed содержит единичный отчет RuStoreReport в свойстве data['report']:
  ```$report = Arr::get($event->data, 'report');```

Отчет RuStoreReport имеет публичный метод target(), возвращающий значение ru-store push токена, на который производилась
отправка.

##### Обработка события успешной отправки

Пример использования события NotificationSent:

```php
    // class SentListener

    /**
     * Обработка успешно отправленных сообщений
     */
    public function handle(NotificationSent $event): void
    {
        match ($event->channel) {
            RuStoreChannel::class => $this->handleRuStoreSuccess($event),
            default => null
        };
    }

    /**
     * Логирование успешно отправленных ru-store-уведомлений
     */
    public function handleRuStoreSuccess(NotificationSent $event): void
    {
        /** @var Collection<string, RuStoreReport> $reports */
        $reports = $event->response;

        $tokens = $reports->map(fn(RuStoreReport $singleReport) => $singleReport->target());
        if ($tokens->isNotEmpty()) {
            /** @var NotifiableUser $notifiable */
            $notifiable = $event->notifiable;

            Log::channel('notifications_ru_store')->info("Успешно отправлены уведомления {$notifiable->short_info}", [
                'tokens' =>  $tokens->toArray(),
            ]);
        }
    }

```

NOTE: Событие NotificationSent поджигается всегда, даже в случае отсутствия успешно отправленных сообщений. Индикатором
успешной отправки является непустая коллекция отчетов.

##### Обработка события неуспешной отправки

Пример использования события NotificationFailed:

```php
    // class FailedSendingListener

    public function handle(NotificationFailed $event): void
    {
        match ($event->channel) {
            RuStoreChannel::class => $this->handleRuStoreFailed($event),
            default => null
        };
    }

    /**
     * Обработка неудачной отправки ru-store-уведомлений
     *
     * @param NotificationFailed $event
     * @return void
     */
    private function handleRuStoreFailed(NotificationFailed $event): void
    {
        /** @var RuStoreReport $report */
        $report = Arr::get($event->data, 'report'); // @todo $report может быть null: падение до отправки!
        /** @var Throwable $e */
        $e = $report->error();
        /** @var NotifiableUser $notifiable */
        $notifiable = $event->notifiable;

        Log::channel('notifications_ru_store')
            ->error("Ошибка отправки уведомления {$notifiable->short_info} " . $e::class, compact('event'));

        if ($e::class === NotFoundException::class || $e::class === InvalidPushTokenException::class) {
            resolve(RuStorePushService::class)->deleteRuStoreToken($notifiable, $report->target(), $e);
        }
    }

```

NOTE: Событие NotificationFailed поджигается только в случае наличия хотя-бы одной неуспешной отправки.
NOTE: В случае, если было выброшено исключение NotFoundException или InvalidPushTokenException, необходимо отозвать
невалидный/недействующий токен.

### Available Message methods

Сообщение RuStoreMessage поддерживает все свойства, описанные
в [документации rustore](https://www.rustore.ru/help/sdk/push-notifications/send-push-notifications)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email yagithub@mail.ru instead of using the issue tracker.

[//]: # (## Contributing)

[//]: # ()

[//]: # (Please see [CONTRIBUTING]&#40;CONTRIBUTING.md&#41; for details.)

## Credits

- [yakOffKa](https://github.com/yakoffka)

[//]: # (- [All Contributors]&#40;../../contributors&#41;)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

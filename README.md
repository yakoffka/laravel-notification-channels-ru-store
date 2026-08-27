Please see [this repo](https://github.com/laravel-notification-channels/channels) for instructions on how to submit a channel proposal.

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

В классе, использующим трейт Notifiable (например User), необходимо реализовать метод, возвращающий массив токенов уведомляемого пользователя:

```php
    /**
     * Получение массива ru-store-push токенов пользователя.
     * Используется пакетом yakoffka/laravel-notification-channels-ru-store (laravel-notification-channels/rustore)
     *
     * @return array
     */
    public function routeNotificationForRuStore(): array
    {
        return $this->ru_store_tokens;
    }
```

Затем создать класс уведомления, в методе via() которого указать канал отправки RuStoreChannel и добавить метод toRuStore():
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
Для контроля отправляемых уведомлений можно воспользоваться событиями, поджигаемыми после отправки:
- cобытие NotificationSent содержит коллекцию отчетов RuStoreSingleReport в свойстве response: ```$report = $event->response;```
- cобытие NotificationFailed содержит коллекцию отчетов RuStoreSingleReport в свойстве data['report']: ```$report = Arr::get($event->data, 'report');```

Коллекция отчетов RuStoreSingleReport содержит данные об отправке уведомлений на конкретное устройство с push-токенами в качестве ключей

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
        /** @var RuStoreReport $report */ // @todo исправить!
        $report = $event->response;

        $report->all()->each(function (RuStoreSingleReport $singleReport, string $token) use ($report, $event): void {
            /** @var Response $response */
            $response = $singleReport->response();
            Log::channel('notifications')->info('RuStoreSuccess Уведомление успешно отправлено', [
                'user' => $event->notifiable->short_info,
                'token' => $token,
                'message' => $report->getMessage()->toArray(),
                'response_status' => $response->status(),
            ]);
        });
    }

```
NOTE: Событие NotificationSent поджигается только в случае наличия успешно отправленных сообщений.


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
     * Обработка неудачных отправок уведомлений через канал RuStore
     *
     * @param NotificationFailed $event
     * @return void
     */
    private function handleRuStoreFailed(NotificationFailed $event): void
    {
        /** @var RuStoreReport $report */ // @todo исправить!
        $report = Arr::get($event->data, 'report');

        $report->all()->each(function (RuStoreSingleReport $singleReport, string $token) use ($report, $event): void {
            $e = $singleReport->error();
            Log::channel('notifications')->error('RuStoreFailed Ошибка отправки уведомления', [
                'user' => $event->notifiable->short_info,
                'token' => $token,
                'message' => $report->getMessage()->toArray(),
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        });
    }

```
NOTE: Событие NotificationFailed поджигается только в случае наличия хотя-бы одной неуспешной отправки.


### Available Message methods

Сообщение поддерживает все свойства, описанные в [документации](https://www.rustore.ru/help/sdk/push-notifications/send-push-notifications)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email yagithub@mail.ru instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [yakOffKa](https://github.com/yakoffka)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

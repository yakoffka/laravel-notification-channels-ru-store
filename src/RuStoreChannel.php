<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;

class RuStoreChannel
{
    // @todo задействовать проверку максимального объема сообщения
    public const MAX_PAYLOAD_LENGTH = 4096;

    /**
     * @param RuStoreClient $client
     */
    public function __construct(private readonly RuStoreClient $client)
    {
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     *
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        $tokens = Arr::wrap($notifiable->routeNotificationFor('ruStore', $notification));

        /** @var RuStoreMessage $message */
        $message = $notification->toRuStore($notifiable);

        Collection::make($tokens)->map(function ($token) use ($message) {
            $this->client->send($token, $message);
        });
    }
}

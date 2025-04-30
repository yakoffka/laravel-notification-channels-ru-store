<?php

namespace NotificationChannels\RuStore;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;

class RuStoreChannel
{
    public const MAX_PAYLOAD_LENGTH = 4078;

    public function __construct()
    {
        // Initialisation code here
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     *
     * @return void
     */
    public function send($notifiable, Notification $notification): void
    {
        //$response = [a call to the api of your notification send]
        //
        // if ($response->error) { // replace this by the code need to check for errors
        //     throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        // }

        $tokens = Arr::wrap($notifiable->routeNotificationFor('ruStore', $notification));

        if (empty($tokens)) {
            return;
        }

        /** @var RuStoreMessage $message */
        $message = $notification->toRuStore($notifiable);

        $response = Collection::make($tokens)->map(function ($token) use ($message) {
            $message->token($token);
            $payload = json_encode(['message' => $message->toArray()], JSON_THROW_ON_ERROR);
            $url = sprintf(config('ru-store.url'), config('ru-store.project_id'));

            $pending_request = Http::withToken(config('ru-store.token'))
                ->withBody($payload)
                ->send('POST', $url);

            // dump($pending_request);
        });
    }
}

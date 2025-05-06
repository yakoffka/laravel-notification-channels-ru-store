<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use Throwable;

class RuStoreChannel
{
    // @todo задействовать проверку максимального объема сообщения
    public const MAX_PAYLOAD_LENGTH = 4096;

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
        $tokens = Arr::wrap($notifiable->routeNotificationFor('ruStore', $notification));

        if (empty($tokens)) {
            return;
        }

        /** @var RuStoreMessage $message */
        $message = $notification->toRuStore($notifiable);

        Collection::make($tokens)->map(function ($token) use ($message) {
            if (!$this->isNonSpaceString($token)) {
                throw CouldNotSendNotification::ruStorePushTokenNotProvided();
            }

            $message->token($token);
            $payload = json_encode(['message' => $message->toArray()], JSON_THROW_ON_ERROR);
            $url = sprintf(config('ru-store.url'), config('ru-store.project_id'));

            try {
                $request = Http::withToken(config('ru-store.token'))->withBody($payload);
                $response = $request->send('POST', $url);
                $response->throw();

            } catch (ClientException $exception) {
                throw CouldNotSendNotification::respondedWithAnError($exception);
            } catch (Throwable $exception) {
                throw CouldNotSendNotification::couldNotCommunicate($exception);
            }
        });
    }

    /**
     * Проверка на непустую строку: аргумент $string должен быть не пустой строкой, содержащей непробельные символы
     *
     * @param string|null $string
     * @return bool
     */
    private function isNonSpaceString(mixed $string): bool
    {
        return is_string($string) && !(preg_match('~\S+~m', $string) !== 1);
    }
}

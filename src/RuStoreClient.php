<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use NotificationChannels\RuStore\Exceptions\CouldNotSendNotification;
use Throwable;

class RuStoreClient
{
    // @todo задействовать проверку максимального объема сообщения
    public const MAX_PAYLOAD_LENGTH = 4096;
    private string $url;

    public function __construct()
    {
        $this->url = sprintf(
            'https://vkpns.rustore.ru/v1/projects/%s/messages:send',
            config('ru-store.project_id')
        );
    }

    /**
     * @param string $token
     * @param RuStoreMessage $message
     * @return PromiseInterface|Response
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function send(string $token, RuStoreMessage $message): PromiseInterface|Response
    {
        // @todo убрать проверку и протестировать
        if (!$this->isNonSpaceString($token)) {
            throw CouldNotSendNotification::ruStorePushTokenNotProvided();
        }

        $message->token($token);
        $payload = json_encode(['message' => $message->toArray()], JSON_THROW_ON_ERROR);

        try {
            $request = Http::withToken(config('ru-store.token'))->withBody($payload);
            $response = $request->send('POST', $this->url);
            $response->throw();

        } catch (ClientException $exception) {
            throw CouldNotSendNotification::respondedWithAnError($exception);
        } catch (Throwable $exception) {
            throw CouldNotSendNotification::couldNotCommunicate($exception);
        }

        return $response;
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

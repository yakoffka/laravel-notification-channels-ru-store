<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\MessageTooLargeException;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use Throwable;

class RuStoreClient
{
    public const MAX_MESSAGE_BYTES = 4096;

    // @todo вынести в настройки?
    private const URL_FORMAT = 'https://vkpns.rustore.ru/v1/projects/%s/messages:send';
    private readonly string $url;
    private readonly string $bearer_token;

    public function __construct()
    {
        $this->url = sprintf(self::URL_FORMAT, config('ru-store.project_id'));
        $this->bearer_token = config('ru-store.token');
    }

    /**
     * Отправка уведомлений на все устройства пользователя
     *
     * @param RuStoreMessage $message
     * @param array<int, string> $tokens
     * @return Collection<int, RuStoreReport>
     */
    public function send(RuStoreMessage $message, array $tokens): Collection
    {
        // @todo проверить тип $token!
        return collect($tokens)->map(fn(string $token): RuStoreReport => $this->sendSingle($message, $token));
    }

    /**
     * Отправка уведомления на конкретное устройство пользователя
     *
     * @param RuStoreMessage $message
     * @param string $token
     * @return RuStoreReport
     */
    public function sendSingle(RuStoreMessage $message, string $token): RuStoreReport
    {
        try {
            $payload = $message->getPayload($token);
            $this->ensureMessageFitsLimit($payload);

            $request = Http::withToken($this->bearer_token)->withBody($payload);
            /** @var PromiseInterface|Response $response */
            $response = $request->send('POST', $this->url);

        } catch (Throwable $exception) {
            return RuStoreReport::failure($token, $exception); // @todo протестировать!
        }

        return $response->successful()
            ? RuStoreReport::success($token, $response)
            : RuStoreReport::failure($token, ResponseExceptionMapper::map($response), $response);
    }

    /**
     * @param string $payload
     * @return void
     * @throws MessageTooLargeException
     */
    private function ensureMessageFitsLimit(string $payload): void
    {
        $bytes = mb_strlen($payload, '8bit');

        if ($bytes > self::MAX_MESSAGE_BYTES) {
            throw new MessageTooLargeException($bytes, self::MAX_MESSAGE_BYTES);
        }
    }
}

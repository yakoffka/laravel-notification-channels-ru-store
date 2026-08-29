<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NotificationChannels\RuStore\Exceptions\InvalidPushTokenException;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\Services\ResponseExceptionMapper;
use NotificationChannels\RuStore\Services\RuStoreMessageValidator;
use Throwable;

class RuStoreClient
{
    private const URL_FORMAT = 'https://vkpns.rustore.ru/v1/projects/%s/messages:send';
    private readonly string $url;
    private readonly string $bearer_token;
    private readonly RuStoreMessageValidator $validator;

    public function __construct(?RuStoreMessageValidator $validator = null)
    {
        $this->url = sprintf(self::URL_FORMAT, config('ru-store.project_id'));
        $this->bearer_token = config('ru-store.token');
        $this->validator = $validator ?? new RuStoreMessageValidator();
    }

    /**
     * Отправка уведомлений на все устройства пользователя
     *
     * @param RuStoreMessage $message
     * @param array<int|string, mixed> $tokens
     * @return Collection<int, RuStoreReport>
     */
    public function send(RuStoreMessage $message, array $tokens): Collection
    {
        return collect($tokens)->map(fn(mixed $token): RuStoreReport => $this->sendSingle($message, $token));
    }

    /**
     * Отправка уведомления на конкретное устройство пользователя
     *
     * @param RuStoreMessage $message
     * @param mixed $token
     * @return RuStoreReport
     */
    public function sendSingle(RuStoreMessage $message, mixed $token): RuStoreReport
    {
        try {
            $token = $this->validator->validateToken($token);
            $payload = $message->getPayload($token);
            $this->validator->ensureMessageFitsLimit($payload);

            $request = Http::withToken($this->bearer_token)->withBody($payload);
            /** @var PromiseInterface|Response $response */
            $response = $request->send('POST', $this->url);

        } catch (InvalidPushTokenException|Throwable $exception) {
            return RuStoreReport::failure($this->reportTarget($token), $exception);
        }

        return $response->successful()
            ? RuStoreReport::success($token, $response)
            : RuStoreReport::failure($token, ResponseExceptionMapper::map($response), $response);
    }

    /**
     * @param mixed $token
     * @return string
     */
    private function reportTarget(mixed $token): string
    {
        return is_string($token) ? $token : '';
    }
}

<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore\Exceptions;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use JsonException;
use Throwable;

class CouldNotSendNotification extends Exception
{
    /**
     * Push-token устройства не указан
     *
     * @return self
     */
    public static function ruStorePushTokenNotProvided(): self
    {
        return new self('To send a notification to a specific user device, you need to provide a token');
    }

    /**
     * Обработка исключения ClientException
     *
     * @param ClientException $exception
     * @return self
     * @throws JsonException
     */
    public static function respondedWithAnError(ClientException $exception): self
    {
        if (!$exception->hasResponse()) {
            return new self('RuStore responded with an error but no response body found');
        }

        $statusCode = $exception->getResponse()->getStatusCode();

        $result = json_decode($exception->getResponse()->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        $description = $result->description ?? 'no description given';

        return new self("RuStore responded with an error `{$statusCode} - {$description}`", 0, $exception);
    }

    /**
     * Ошибка связи с сервером RuStore
     */
    public static function couldNotCommunicate(Throwable $e): self
    {
        return new self(
            'The communication with RuStore failed. "' . $e->getMessage() . '"'
        );
    }

    /**
     * @param PromiseInterface|Response $response
     * @return static
     */
    public static function serviceRespondedWithAnError(PromiseInterface|Response $response)
    {
        // $response->throw();
        // dd($response);
        return new static("Descriptive error message.");
    }
}

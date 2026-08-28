<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use NotificationChannels\RuStore\Exceptions\InvalidArgumentException;
use NotificationChannels\RuStore\Exceptions\NotFoundException;
use NotificationChannels\RuStore\Exceptions\PermissionDeniedException;
use NotificationChannels\RuStore\Exceptions\RuStoreInternalException;
use NotificationChannels\RuStore\Exceptions\TooManyRequestsException;
use NotificationChannels\RuStore\Exceptions\UnexpectedException;
use Throwable;

/**
 * Преобразует HTTP-ответ в исключение.
 */
class ResponseExceptionMapper
{
    /**
     * Преобразование ответов в исключение
     *  {
     *      "error" : {
     *      "code" :  400 ,
     *          "message" :  "The registration token is not a valid FCM registration token" ,
     *          "status" :  "INVALID_ARGUMENT"
     *      }
     *  }
     * @param PromiseInterface|Response $response
     * @return Throwable
     */
    public static function map(PromiseInterface|Response $response): Throwable
    {
        $code = $response->getStatusCode();
        $decoded = $response->json();
        $message = Arr::get($decoded, 'error.message');
        $status = Arr::get($decoded, 'error.status');
        // $body = $response->body(); // на случай, если не получим $message/$status

        // Используем константы из Symfony Response
        $exception = match (true) {
            $status === 'INVALID_ARGUMENT' => InvalidArgumentException::class,
            $status === 'INTERNAL' => RuStoreInternalException::class,
            $status === 'TOO_MANY_REQUESTS' => TooManyRequestsException::class,
            $status === 'PERMISSION_DENIED' => PermissionDeniedException::class,
            $status === 'NOT_FOUND' => NotFoundException::class,
            default => null,
        };

        if ($exception !== null) {
            return new $exception($message, $code);
        }

        // // Обработка диапазонов
        // if ($code >= 400 && $code < 500) {
        //     return new ClientErrorException($response);
        // }
        //
        // if ($code >= 500 && $code < 600) {
        //     return new ServerErrorException($response);
        // }

        return new UnexpectedException($response->body(), $code);
    }
}

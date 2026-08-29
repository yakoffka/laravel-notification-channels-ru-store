<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Exceptions;

use Exception;
use Throwable;

/**
 * Превышено количество попыток отправить сообщение.
 */
class TooManyRequestsException extends Exception
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

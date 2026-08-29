<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Exceptions;

use Exception;
use Throwable;

/**
 * Неправильно указан push-токен пользователя
 */
class NotFoundException extends Exception
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
